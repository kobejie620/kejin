!(function () {
    window._RES_ = window._RES_ || function () {
        var a = document.scripts, b = a[a.length - 1].src;
        return  b.substring(0, b.lastIndexOf("/") - 2);
    }();

    window.hook_image_error = function (img) {
        if (img.className && img.className.indexOf('upload_one_img') === -1) {
            img.src = _RES_ + '/img/none.png';
        } else {
            img.src = _RES_ + '/img/image.png';
        }
        img.setAttribute('data-src', img.src);
        return false;
    };
})();
