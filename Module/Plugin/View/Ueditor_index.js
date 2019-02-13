window._plugin_source_domin = "{$source_domin}";
window._plugin_editor_path_ = '__LIB__/qiniuue/';
window._plugin_editor_token_ = '__APP__/plugin/ueditor/getToken.js?token={$token}';
window._plugin_editor_server = '__APP__/plugin/ueditor/server.js?token={$token}';

!$(function () {
    /**
     * 文件、图片 公共上传的方法
     * @param {type} input 内容载体ID
     * @param {type} callback 上传成功后的回调方法 参数为文件信息
     * @param {type} type 需要上传的文件类型 Img File
     * @param {type} fileType 文件类型
     */
    $.createUpload = function (ele, callback, type, fileType) {
        /* 重新实例化一个编辑器，防止在上面的editor编辑器中显示上传的图片或者文件 */
        var id = 'edit_' + Math.ceil(Math.random() * 10000);
        window.editor = window.editor || {};
        var $this = $(ele);
        var online = $(ele).attr('data-online') ? true : false;

        /* 如果对象已经定义，需要销毁对象 */
        jQuery('<div class="hidden" id="{id}"></div>'.replace('{id}', id)).appendTo('body');
        var option = {autoHeightEnabled: false};
        if (fileType) {
            if (typeof fileType === 'string') {
                fileType = fileType.split(',');
                for (var i in fileType) {
                    if (fileType[i].indexOf('.') !== 0) {
                        fileType[i] = '.' + fileType[i];
                    }
                }
            }
            option.imageManagerAllowFiles = fileType;
            option.imageAllowFiles = fileType;
            option.fileManagerAllowFiles = fileType;
            option.fileAllowFiles = fileType;
            option.videoAllowFiles = fileType;
        }
        var _editor = window.editor[id] = UE.getEditor(id, option);
        _editor.ready(function () {
            _editor.hide(); /* 隐藏编辑器 */
            /* 侦听图片上传 */
            _editor.addListener('afterInsertImage', callback);
            /* 侦听文件上传 */
            _editor.addListener('afterInsertFile', callback);
        });
        $this.on('click', function () {
            _editor.setOpt('insertAlign', false);
            switch (type) {
                case 'insertimage':
                    _editor.setOpt('onlineImage', online);
                    _editor.setOpt('imageActionName', 'uploadimage');
                    break;
                case 'attachment':
                    _editor.setOpt('onlineFile', online);
                    _editor.setOpt('fileActionName', 'uploadfile');
                    break;
                case 'insertvideo':
                    _editor.setOpt('videoActionName', 'uploadvideo');
                    break;
                default:
                    _editor.setOpt('fileActionName', 'uploadfile');
                    type = 'attachment';
            }
            try {
                _editor.getDialog(type).open();
            } catch (e) {
                console.dir(e);
            }
        });
    };

    /**
     * 若不存在跨域时
     * 监听文件上传事件
     */
    if (window.document.domain === window._plugin_source_domin) {
        /**
         * 定时器
         */
        window.setInterval(function () {

            /*富文本编辑器实例*/
            $('.editor:not([loaded]),[data-editor]:not([loaded])').map(function () {
                $(this).attr('loaded', '');
                $(this).parents('.form-control').add(this).removeClass('form-control');
                this.id = '_editor_' + Math.floor(Math.random() * 1000000);
                UE.getEditor(this.id);
            });

            /* 单图片上传 */
            $('.upload_one_img:not([loaded]),[data-upload-one-img]:not([loaded])').map(function () {
                var $this = $(this).attr('loaded', '');
                var name = $this.data('name');
                var $name = $('input[name="' + name + '"]');
                $.createUpload(this, function (i, args) {
                    for (var i in args) {
                        $this.attr('src', args[i].src);
                        $name.val(args[i].src).trigger('change');
                    }
                }, 'insertimage', $this.attr('data-type'));
            });

            /* 上传文件 */
            $('.upload_one_file:not([loaded]),[data-upload-one-file]:not([loaded])').map(function () {
                var $this = $(this).attr('loaded', '');
                var name = $this.data('name');
                var $name = $('input[name="' + name + '"]');
                $.createUpload(this, function (i, args) {
                    for (var i in args) {
                        $name.val(args[i].url).trigger('change');
                    }
                }, 'attachment', $this.attr('data-type'));
            });

            /* 上传视频 */
            $('.upload_one_video:not([loaded]),[data-upload-one-video]:not([loaded])').map(function () {
                var $this = $(this).attr('loaded', '');
                var name = $this.data('name');
                var $name = $('input[name="' + name + '"]');
                $.createUpload(this, function (i, args) {
                    for (var i in args) {
                        $name.val(args[i].url).trigger('change');
                    }
                }, 'insertvideo', $this.attr('data-type'));
            });

            /* 多图片上传 */
            $('.upload_multi_img:not([loaded]),[data-upload-multi-img]:not([loaded])').map(function () {
                var $this = $(this).attr('loaded', '');
                var name = $this.data('name');
                var $name = $('input[name="' + name + '"]');
                $.createUpload(this, function (i, args) {
                    var srcs = $name.val() ? $name.val().split('|') : [];
                    for (var i in args) {
                        srcs.push(args[i].src);
                    }
                    $name.val(srcs.join('|')).trigger('change');
                }, 'insertimage', $this.attr('data-type'));

                /* 图片预览与删除操作 */
                if (!$this.attr('data-show-class') && !$this.attr('data-show-id')) {
                    return;
                }
                var tpl = '<div style="display:inline-block;margin:2px;overflow:hidden">'
                        + '<img alt="" style="width:120px;height:80px;" src="{src}"/>'
                        + '<b style="position:absolute;width:18px;height:18px;cursor:pointer;margin-left:-18px;text-align:center;color:#fff;background:rgba(0,0,0,.5);display:none">×</b>'
                        + '</div>';
                $name.bind('change', function () {
                    var $container = $('.' + $this.attr('data-show-class')).add($('#' + $this.attr('data-show-id'))).empty();
                    if (!($name.val())) {
                        return;
                    }
                    var srcs = $name.val().split('|');
                    for (var i in srcs) {
                        $(tpl.replace('{src}', srcs[i])).appendTo($container).hover(function () {
                            $(this).find('b').css('display', 'inline-block');
                        }, function () {
                            $(this).find('b').css('display', 'none');
                        }).find('b').on('click', function () {
                            var src = $(this).prev('img').attr('src');
                            var _srcs = new Array();
                            for (var j in srcs) {
                                if (srcs[j] !== src) {
                                    _srcs.push(srcs[j]);
                                }
                            }
                            srcs = _srcs;
                            $name.val(_srcs.join('|'));
                            $(this).hide().parent('div').animate({width: 0, opacity: 0}, function () {
                                $(this).remove();
                            });
                        });
                    }
                }).trigger('change');
            });
        }, 500);
    }
});