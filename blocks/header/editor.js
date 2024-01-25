let currentFeaturedImageId = 0;
let setFeaturedImageId = 0;

// Function to get attachment custom field data using AJAX
function getAttachmentCustomField(attachmentId, callback) {
    // Send an AJAX request to the custom handler.
    jQuery.ajax({
        type: 'POST',
        url: '/ajax', // WordPress AJAX URL
        data: {
            action: 'get_attachment_custom_field', // Custom AJAX action name
            image_id: attachmentId // Pass the attachment ID
        },
        success: function(response) {
            // Handle the response (custom field data) here.
            callback(response); // Pass the custom field data to the callback function
        },
        error: function(error) {
            // Handle AJAX errors here.
            console.error('AJAX Error:', error);
        }
    });
}

wp.domReady(function() {
    // Function to open the media library
    // Function to open the media library and select an image
    function openMediaLibrary() {
        const mediaUploader = wp.media({
            title: 'Select cover image',
            button: { text: 'Insert' },
            multiple: false,
            library: { type: 'image' }
        });

        mediaUploader.off('select').on('select', function() {
            const attachment = mediaUploader.state().get('selection').first().toJSON();
            console.log("Selected Image:", attachment.id); // Debug log
            updateFeaturedImage(attachment.id, attachment.url);
        });

        mediaUploader.open();
    }


    // Function to update the featured image in the interface and state
    function updateFeaturedImage(imageId, imageUrl) {
        getAttachmentCustomField(imageId, function(customFieldData) {
            const featuredImageContainer = jQuery('#featured-image-container');
            if (featuredImageContainer.length > 0) {
                // Assuming customFieldData contains properties x and y
                let positionStyle = customFieldData ? `object-position:${customFieldData.x}% ${customFieldData.y}%;` : '';
                featuredImageContainer.html(`<img src="${imageUrl}" alt="Cover Image" style="${positionStyle}"/>`);
                featuredImageContainer.attr('data-image-id', imageId);
                jQuery('#featured-image-button').html('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path fill="#a83232" d="M13 19C13 19.7 13.13 20.37 13.35 21H5C3.9 21 3 20.11 3 19V5C3 3.9 3.9 3 5 3H19C20.11 3 21 3.9 21 5V13.35C20.37 13.13 19.7 13 19 13V5H5V19H13M11.21 15.83L9.25 13.47L6.5 17H13.35C13.75 15.88 14.47 14.91 15.4 14.21L13.96 12.29L11.21 15.83M20 18V15H18V18H15V20H18V23H20V20H23V18H20Z" /></svg>Remove cover');
            }
            console.log("Image selected with ID:", imageId);
        });
    }


    function removeFeaturedImage() {
        const featuredImageContainer = jQuery('#featured-image-container');
        if (featuredImageContainer.length > 0) {
            featuredImageContainer.empty();
            featuredImageContainer.attr('data-image-id', '0');
            jQuery('#featured-image-button').html('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path fill="#32a85a" d="M13 19C13 19.7 13.13 20.37 13.35 21H5C3.9 21 3 20.11 3 19V5C3 3.9 3.9 3 5 3H19C20.11 3 21 3.9 21 5V13.35C20.37 13.13 19.7 13 19 13V5H5V19H13M13.96 12.29L11.21 15.83L9.25 13.47L6.5 17H13.35C13.75 15.88 14.47 14.91 15.4 14.21L13.96 12.29M20 18V15H18V18H15V20H18V23H20V20H23V18H20Z" /></svg>Add cover'); // Update the button text
        }
        console.log("Removed Image. New data-image-id:", featuredImageContainer.attr('data-image-id')); // Debugging log
    }



    jQuery('#featured-image-button').on('click', function() {
        const imageId = jQuery('#featured-image-container').attr('data-image-id');
        if (imageId !== '0') {
            removeFeaturedImage();
        } else {
            openMediaLibrary();
        }
    });


    jQuery('#featured-image-container').on('click', function() {
        openMediaLibrary(); // Make sure this is triggering correctly
    });

    // Function to update the block editor's post title
    function updateBlockEditorTitle() {
        const customPostTitleField = jQuery('#postTitle');
        if (customPostTitleField) {
            const customTitleValue = customPostTitleField.val();
            wp.data.dispatch('core/editor').editPost({ title: customTitleValue });
        }
    }

    // Function to update the post thumbnail metabox
    function updatePostThumbnailMetabox(imageUrl) {
        // Find the post thumbnail metabox container
        const postThumbnailMetabox = jQuery('#postimagediv');

        // Check if the metabox container exists
        if (postThumbnailMetabox.length > 0) {
            // Find the thumbnail image element in the metabox
            const thumbnailImage = postThumbnailMetabox.find('.inside img');

            // Update the thumbnail image's src attribute with the new image URL
            if (thumbnailImage.length > 0) {
                thumbnailImage.attr('src', imageUrl);
            }
        }
    }

    // Function to load JSON data from #cover-data
    function loadCoverData() {
        const coverDataContainer = document.getElementById('cover-data');
        if (coverDataContainer) {
            const coverDataJson = coverDataContainer.textContent.trim();
            try {
                const coverData = JSON.parse(coverDataJson);
                if (coverData.image_id) {
                    // Set the default image based on the cover data
                    currentFeaturedImageId = coverData.image_id;
                    const featuredImageContainer = document.getElementById('featured-image-container');
                    if (featuredImageContainer) {
                        featuredImageContainer.innerHTML = `<img src="${coverData.image_url}" alt="Cover Image" style="object-position:${coverData.focal_point.x + '% ' + coverData.focal_point.y + '%'};" />`;
                        featuredImageContainer.setAttribute('data-image-id', currentFeaturedImageId);
                        jQuery('#featured-image-button').html('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path fill="#a83232" d="M13 19C13 19.7 13.13 20.37 13.35 21H5C3.9 21 3 20.11 3 19V5C3 3.9 3.9 3 5 3H19C20.11 3 21 3.9 21 5V13.35C20.37 13.13 19.7 13 19 13V5H5V19H13M11.21 15.83L9.25 13.47L6.5 17H13.35C13.75 15.88 14.47 14.91 15.4 14.21L13.96 12.29L11.21 15.83M20 18V15H18V18H15V20H18V23H20V20H23V18H20Z" /></svg>Remove cover');
                    }
                }
            } catch (error) {
                console.error('Error parsing cover data JSON:', error);
            }
        }
    }

    jQuery(document).on('click', '#icon-toggle-button', function() {
        var postId = wp.data.select('core/editor').getCurrentPostId(); // Get the current post ID

        console.log(postId);

        jQuery.ajax({
            url: '/ajax', // This is a global variable in WordPress admin
            type: 'POST',
            data: {
                'action': 'toggle_post_sticky_status', // The PHP function to trigger
                'post_id': postId // The ID of the post to toggle
            },
            success: function(response) {
                jQuery('#icon-toggle-button').html(response);
                // Additional success handling
            }
        });
    });


    // Prepend the custom interface to the block-editor-writing-flow
    const timer = setInterval(function () {
        const currentTitle = jQuery('.editor-post-title__input').text();
        const currentSubtitle = jQuery('#wpsubtitle').val();
        // Create your custom interface with a large post title field
        const customInterface = `
        <div class="editor-head">
            <div id="featured-image-container" title="Change cover" data-image-id="0"></div>
            <button id="featured-image-button" class="button"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path fill="#32a85a" d="M13 19C13 19.7 13.13 20.37 13.35 21H5C3.9 21 3 20.11 3 19V5C3 3.9 3.9 3 5 3H19C20.11 3 21 3.9 21 5V13.35C20.37 13.13 19.7 13 19 13V5H5V19H13M13.96 12.29L11.21 15.83L9.25 13.47L6.5 17H13.35C13.75 15.88 14.47 14.91 15.4 14.21L13.96 12.29M20 18V15H18V18H15V20H18V23H20V20H23V18H20Z" /></svg>Add cover</button>
            <div class="title-field">
                <button id="icon-toggle-button"></button>
                <input type="text" id="postTitle" name="editor-post-title" style="width: 100%; font-size: 24px; font-weight: bold; font-family: sans-serif;" value="${currentTitle}" placeholder="Entry Title..." />
            </div>
            <input type="text" id="postSubtitle" name="editor-post-subtitle" style="width: 100%; font-size: 20px; font-weight: 500;" value="${currentSubtitle}" placeholder="Entry Subtitle..." />
            <style>
                .editor-head {
                    max-width: 840px;
                    margin: 4em auto 1em;
                }
                .editor-head input[type="text"] {
                    border: none;
                    padding: 0;
                    box-shadow: none;
                }
                #featured-image-container {
                    margin: 0 -12px;
                }
                #featured-image-container > img {
                    display: block;
                    cursor: pointer;
                    aspect-ratio: 21 / 9;
                    object-fit: cover;
                    border-radius: 8px;
                }
                #featured-image-button {
                    display: flex;
                    flex-direction: row;
                    gap: 4px;
                    background: transparent;
                    border: none;
                    box-shadow: none;
                    padding: 0;
                    margin-top: 8px;
                    align-items: center;
                    font-size: 1.025rem;
                    outline: 0;
                    font-family: sans-serif;
                }
                #featured-image-button svg {
                    width: 1em;
                    height: 1em;
                    -webkit-transform: scale(0.85) translateY(-2px);
                    transform: scale(0.85) translateY(-2px);
                }
                .title-field {
                    position: relative;
                }
                #icon-toggle-button {
                    position: absolute;
                    inset: 0 auto 0 -4em;
                    display: block;
                    background: none;
                    border: none;
                    cursor: pointer;
                }
                #icon-toggle-button svg {
                    display: block;
                    width: 2em;
                    height: 2em;
                }
                #icon-toggle-button svg:not(:first-of-type) {
                    display: none;
                }
            </style>
        </div>
    `;

        const blockEditorWritingFlow = jQuery('.block-editor-writing-flow');
        if ( blockEditorWritingFlow.length > 0 ) {
            blockEditorWritingFlow.prepend(customInterface);

            const customPostTitleField = jQuery('#postTitle');
            if (customPostTitleField) {
                customPostTitleField.on('input', updateBlockEditorTitle);
                customPostTitleField.keydown(function(event) {
                    if (event.which === 13) {
                        event.preventDefault();
                        jQuery('#postSubtitle').focus();

                        // Move the cursor to the end of #postSubtitle
                        const postSubtitleField = document.getElementById('postSubtitle');
                        if (postSubtitleField) {
                            const length = postSubtitleField.value.length;
                            postSubtitleField.setSelectionRange(length, length);
                        }
                    }
                });
            }

            const customPostSubtitleField = jQuery('#postSubtitle');
            if (customPostSubtitleField) {
                customPostSubtitleField.on('input', function() {
                    jQuery('#wpsubtitle').val(jQuery(this).val());
                });
                customPostSubtitleField.keydown(function(event) {
                    if (event.which === 13) {
                        event.preventDefault();
                        jQuery('.wp-block-post-content > *:first-child').focus();

                        // Focus on the first editable block (p tag)
                        const firstBlock = document.querySelector('.block-editor-rich-text__editable:first-child');
                        if (firstBlock) {
                            const firstBlockContent = firstBlock.textContent;
                            const firstBlockLength = firstBlockContent.length;
                            const range = document.createRange();
                            range.setStart(firstBlock.firstChild, firstBlockLength);
                            range.setEnd(firstBlock.firstChild, firstBlockLength);
                            const selection = window.getSelection();
                            selection.removeAllRanges();
                            selection.addRange(range);
                        }
                    }
                });
            }

            // Attach a click event to the featured image button
            const featuredImageButton = jQuery('#featured-image-button');
            if (featuredImageButton) {
                featuredImageButton.on('click', function() {
                    // Check if there is an image in the container
                    const featuredImageContainer = jQuery('#featured-image-container');
                    if (featuredImageContainer.find('img').length > 0) {
                        // If an image is present, remove it
                        removeFeaturedImage();
                    } else {
                        // If no image is present, open the media library
                        openMediaLibrary();
                    }
                });
            }

            const featuredImageContainer = jQuery('#featured-image-container');
            featuredImageContainer.on('click', function() {
                openMediaLibrary(); // Trigger the media library dialog
            });

            loadCoverData();

            if ( jQuery('#inspector-checkbox-control-0').prop('checked') ) {
                jQuery('#icon-toggle-button').html('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M16,12V4H17V2H7V4H8V12L6,14V16H11.2V22H12.8V16H18V14L16,12Z"></path></svg>');
            } else {
                jQuery('#icon-toggle-button').html('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path fill-opacity="0.35" d="M8,6.2V4H7V2H17V4H16V12L18,14V16H17.8L14,12.2V4H10V8.2L8,6.2M20,20.7L18.7,22L12.8,16.1V22H11.2V16H6V14L8,12V11.3L2,5.3L3.3,4L20,20.7M8.8,14H10.6L9.7,13.1L8.8,14Z"></path></svg>');
            }

            clearInterval(timer);
        }
    }, 100);
});

let wasSaving = false;

wp.data.subscribe(function() {
    const isSaving = wp.data.select('core/editor').isSavingPost();
    const postId = wp.data.select('core/editor').getCurrentPostId();
    const imageId = jQuery('#featured-image-container').attr('data-image-id');

    // Check if just finished saving
    if (wasSaving && !isSaving) {
        console.log("Save completed. Image ID:", imageId);
        console.log("Triggering AJAX request to set featured image with ID:", imageId);
        setFeaturedImageWithAjax(postId, imageId);
    }

    // Update wasSaving for the next check
    wasSaving = isSaving;
});





function setFeaturedImageWithAjax(postId, imageId) {
    console.log("Sending AJAX request for Post ID:", postId, "Image ID:", imageId); // Debugging log

    jQuery.ajax({
        type: 'POST',
        url: '/ajax',
        data: {
            action: 'set_featured_image',
            post_id: postId,
            image_id: imageId
        },
        success: function(response) {
            // Handle success
        },
        error: function(error) {
            // Handle error
        }
    });
}