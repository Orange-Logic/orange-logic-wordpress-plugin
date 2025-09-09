(function($) {
    'use strict';

    var GAB = {
        /**
         * Initialize the plugin
         */
        init: function() {
            this.loader = this.createLoader();
            $('body').append(this.loader);

            if (window.location.href.includes('gab-media')) {
                this.fetchAssets(false);
            }

            this.extendMediaFrame();
        },

        /**
         * Create loading overlay
         * @returns {jQuery} Loader element
         */
        createLoader: function() {
            return $([
                '<div id="orange-logic-loader" style="',
                'position: fixed;',
                'top: 0;',
                'left: 0;',
                'width: 100%;',
                'height: 100%;',
                'background: rgba(0, 0, 0, 0.7);',
                'display: none;',
                'align-items: center;',
                'justify-content: center;',
                'color: white;',
                'font-size: 18px;',
                'font-weight: bold;',
                'z-index: 9999999999;">',
                '<div style="display: flex; align-items: center; gap: 10px;">',
                '<img src="' + gabConnector.siteUrl + '/wp-admin/images/spinner.gif" alt="Loading..." style="width: 24px; height: 24px;">',
                '</div>',
                '</div>'
            ].join(''));
        },

        /**
         * Extend WordPress Media Frame
         */
        extendMediaFrame: function() {
            var originalMediaFrame = wp.media.view.MediaFrame.Select;
            wp.media.view.MediaFrame.Select = originalMediaFrame.extend({
                initialize: function() {
                    originalMediaFrame.prototype.initialize.apply(this, arguments);
                    this.on('content:render', this.updateSelectButtonVisibility, this);
                    this.on('router:render:browse', this.addOrangeLogicTab, this);
                    this.on('content:render:orangelogic', this.renderOrangeLogicContent, this);
                },

                addOrangeLogicTab: function(routerView) {
                    routerView.set({
                        'orangelogic': {
                            id: 'orange_logic_assets',
                            text: 'Orange Logic Assets',
                            priority: 60
                        }
                    });
                },

                renderOrangeLogicContent: function() {
                    var view = new wp.media.View({
                        controller: this,
                        className: 'orangelogic-tab-content'
                    });

                    this.content.set(view);
                    view.$el.html('<div id="gab_browser_container" style="display: flex; align-items: center; height: calc(100vh - 215px); justify-content: center; width: 100%;"></div>');

                    var self = this;
                    setTimeout(function() {
                        self.updateSelectButtonVisibility();
                        GAB.fetchAssets(true);
                    }, 500);
                },

                updateSelectButtonVisibility: function() {
                    var $selectButton = $('.media-modal-content').find('.media-button-select');
                    var isFeaturedImageFrame = this._state === 'featured-image';
                    var isOrangeLogicTab = this.content.mode() === 'orangelogic';

                    $selectButton.toggle(!(isFeaturedImageFrame && isOrangeLogicTab));
                }
            });
        },

        /**
         * Fetch assets using Cortex Asset Picker
         * @param {boolean} isMediaModal - Whether this is in media modal
         */
        fetchAssets: function(isMediaModal) {
            OrangeDAMContentBrowser.open({
                onAssetSelected: (assets) => {
                    console.log('onAssetSelected: ',assets);
                    var image = assets[0];
                    if (!image || !image.imageUrl || !image.extraFields['CoreField.Identifier']) {
                        alert('No image selected.');
                        return;
                    }
                    var imageData = {
                        image_title: image.extraFields["CoreField.Title"] || '',
                        image_caption: image.extraFields["CoreField.CaptionLong"] || '',
                        image_alt: image.extraFields["CoreField.CaptionLong_3"] || ''
                    };

                    if (!isMediaModal) {
                        GAB.downloadImage(image.imageUrl, image.extraFields['CoreField.Identifier'], imageData);
                    } else if (wp.media.frame && wp.media.frame._state === 'featured-image') {
                        GAB.setFeaturedImage(image.imageUrl, image.extraFields['CoreField.Identifier'], imageData);
                    } else {
                        GAB.insertImageIntoEditor(image.imageUrl, imageData);
                    }
                },
                onError: function(errorMessage, error) {
                    console.error('Asset Picker Error:', errorMessage, error);
                },
                multiSelect: false,
                containerId: "gab_browser_container",
                baseUrl: gabConnector.orangedam_url,
                onlyIIIFPrefix: true,
                displayInfo: {
                    title: true,
                    dimension: true,
                    fileSize: false,
                    tags: false
                },
                extraFields: [
                    "Identifier",
                    "CoreField.Identifier",
                    "CoreField.pixel-width",
                    "CoreField.Title",
                    "CoreField.alternative-description",
                    "CoreField.CaptionLong",
                    "CoreField.CaptionLong_3"
                ]
            });
        },

        /**
         * Download image and redirect to edit page
         * @param {string} imageUrl - Image URL
         * @param {string} imageId - Image ID
         * @param {Object} imageData - Image metadata
         */
        downloadImage: function(imageUrl, imageId, imageData) {
            this.loader.show();
            $.ajax({
                url: gabConnector.ajaxUrl,
                method: 'POST',
                data: {
                    action: 'gab_download_image',
                    image_url: imageUrl,
                    image_id: imageId,
                    image_data: imageData,
                    nonce: gabConnector.nonce
                },
                success: function(response) {
                    if (response.success) {
                        window.location.href = gabConnector.siteUrl + '/wp-admin/post.php?post=' + response.data.attachment_id + '&action=edit';
                    } else {
                        alert('Failed to download image.');
                    }
                },
                error: function(xhr, status, error) {
                    alert('Error while downloading image.');
                },
                complete: function() {
                    GAB.loader.hide();
                }
            });
        },

        /**
         * Insert image into editor
         * @param {string} imageUrl - Image URL
         * @param {Object} imageData - Image metadata
         */
        insertImageIntoEditor: function(imageUrl, imageData) {
            this.loader.show();
            var html = '<img src="' + imageUrl + '" alt="' + imageData.image_alt + '" style="max-width:100%;height:auto;" />';

            if (typeof tinymce !== 'undefined' && tinymce.activeEditor) {
                tinymce.activeEditor.execCommand('mceInsertContent', false, html);
            } else if (wp.data && wp.data.select('core/editor')) {
                var blockEditor = wp.data.dispatch('core/block-editor');
                var block = wp.blocks.createBlock('core/image', { url: imageUrl, alt: imageData.image_alt });
                blockEditor.insertBlocks(block);
            }

            wp.media.frame.close();
            this.loader.hide();
        },

        /**
         * Set featured image
         * @param {string} imageUrl - Image URL
         * @param {string} imageId - Image ID
         * @param {Object} imageData - Image metadata
         */
        setFeaturedImage: function(imageUrl, imageId, imageData) {
            this.loader.show();
            wp.media.frame.close();

            $.ajax({
                url: gabConnector.ajaxUrl,
                method: 'POST',
                data: {
                    action: 'set_orange_logic_featured_image',
                    post_id: $('#post_ID').val(),
                    image_url: imageUrl,
                    image_id: imageId,
                    image_data: imageData,
                    nonce: gabConnector.nonce
                },
                success: function(response) {
                    if (response.success) {
                        wp.data.dispatch('core/editor').editPost({ featured_media: response.data.attachment_id });
                        wp.data.dispatch('core').invalidateResolution('getMedia', [response.data.attachment_id]);
                    } else {
                        alert('Failed to set featured image.');
                    }
                },
                error: function(xhr, status, error) {
                    alert('Error while setting featured image.');
                },
                complete: function() {
                    setTimeout(function() {
                        GAB.loader.hide();
                    }, 1500);
                }
            });
        }
    };

    $(document).ready(function() {
        GAB.init();
    });

})(jQuery);