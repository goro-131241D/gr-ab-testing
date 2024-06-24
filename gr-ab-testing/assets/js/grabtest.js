/**
 * Executes the provided callback function when the DOM content is loaded.
 * Attaches event listeners to elements with the class 'abtest-link'.
 * @param {Event} event - The DOMContentLoaded event object.
 */
function handleClick(event) {
    if (event.button === 0 || event.button === 1) {
        event.preventDefault(); // Prevent the default link behavior
        const linkId = event.currentTarget.dataset.grabLink; // Get the value of the data-link-id attribute
        const redirect = event.button === 0; // Left click should redirect, middle click should not
        //alert('linkId: ' + linkId + ' redirect: ' + redirect + ' href: ' + event.target.href);
        sendData(linkId, event.target.href, redirect);
    }
}
// Attach event listeners to elements with the class 'abtest-link'

document.querySelectorAll('.grab-link').forEach(function (element) {
    element.addEventListener('mousedown', handleClick);
});

/**
 * Sends data to the server using AJAX.
 * @param {string} linkId - The link ID.
 * @param {string} redirectUrl - The URL to redirect to.
 * @param {boolean} redirect - Whether to perform a redirect.
 */
function sendData(linkId, redirectUrl, redirect) {
    jQuery.ajax({
        type: 'POST',
        url: grabLocalizeData.ajaxurl,
        data: {
            action: 'gr_abtest_post', // Action name
            grAbtestPostNonce: grabLocalizeData.grabPostNonce, // Nonce
            postId: grabLocalizeData.postId,
            abtestId: grabLocalizeData.abtestId,
            linkId: linkId,
            uuid: grabLocalizeData.uuid,
            timeStamp: getFormattedTimestamp(),
        }
    }).done(function (response) {
        console.log(response);
        if (redirect) {
            window.location.href = redirectUrl;
        }
    }).fail(function (error) {
        console.warn(`Failed: ${error.status} (${error.statusText})`);
    });
}

/**
 * Returns the current timestamp in the format 'YYYY-MM-DD HH:MM:SS'.
 * @returns {string} The formatted timestamp.
 */
function getFormattedTimestamp() {
    var date = new Date();
    var year = date.getFullYear();
    var month = ('0' + (date.getMonth() + 1)).slice(-2);
    var day = ('0' + date.getDate()).slice(-2);
    var hours = ('0' + date.getHours()).slice(-2);
    var minutes = ('0' + date.getMinutes()).slice(-2);
    var seconds = ('0' + date.getSeconds()).slice(-2);
    var formattedDate = year + '-' + month + '-' + day + ' ' + hours + ':' + minutes + ':' + seconds;
    return formattedDate;
}





