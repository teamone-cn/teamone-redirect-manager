// simple redirect
if ('undefined' == typeof redirect_location ) {
    var redirect_location = {url:''};
}
window.location.replace(redirect_location.url);