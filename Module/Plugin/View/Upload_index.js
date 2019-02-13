!$(function () {
    /**
     * 加载依赖库
     */
    $.ajax('__LIB__/upload/qiniu.zip.min.js', {async: false, cache: true, dataType: 'script', success: function () {
            /**
             * 文件上传jQuery插件
             * 
             * @param {type} option
             * @returns {undefined}
             * @author zoujingli <zoujingli@qq.com>
             */
            $.fn.upload = function (option) {
                /* 变量初始化 */
                var $self = this;

                if ($self.size() < 1) {
                    return;
                }

                $self.data('html', $self.html() || $self.val());

                if ($self.data('uploader')) {
                    return $self.data('uploader');
                }

                option = option || {};
                option.node = $self[0];

                /* 返回值接收ID */
                option.name = $self.data('name') || option.name || 'upload';
                option.input = $('[name="' + option.name + '"]');

                /* 文件筛选器 */
                option.file_type = $self.data('type') || option.file_type || '*';
                option.file_desc = $self.data('desc') || option.file_desc || '请选择文件...';

                option.multi_selection = $self.data('multi_selection') || $self.data('multi') || option.multi_selection || option.multi || false;

                /**
                 * 指定上传的文件类型
                 * @type Array
                 */
                var _mime_types = [{title: option.file_desc, extensions: option.file_type}];

                /**
                 * 初始化参数
                 * @type type
                 */
                var _default = {
                    /* 是否启用多文件上传 */
                    multi_selection: option.multi_selection,
                    /* 上传模式,依次退化 */
                    runtimes: 'html5,flash,silverlight,html4',
                    /* 文件选择过滤文件 */
                    /* 上传选择的点选按钮，**必需** */
                    browse_button: option.node,
                    /* upToken*/
                    uptoken: '{$upload_token}',
                    /* 若开启该选项，SDK会为每个文件自动生成key（文件名） */
                    unique_names: "{$config.qiniu_rename}" ? true : false,
                    /* 若在服务端生成uptoken的上传策略中指定了 `sava_key`，则开启，SDK在前端将不对key进行任何处理 */
                    save_key: false,
                    /* bucket 域名，下载资源时用到，**必需** */
                    domain: '{$upload_domain}',
                    /* 上传区域DOM ID，默认是browser_button的父元素， */
                    //container: 'body',
                    /* 最大文件体积限制 */
                    max_file_size: 0,
                    /* 引入flash,相对路径 */
                    flash_swf_url: '__LIB__/upload/Moxie.swf',
                    silverlight_xap_url: '__LIB__/upload/Moxie.xap',
                    /* 上传失败最大重试次数 */
                    max_retries: 1,
                    /* 开启可拖曳上传 */
                    dragdrop: false,
                    /* 拖曳上传区域元素的ID，拖曳文件或文件夹后可触发上传 */
                    drop_element: 'body',
                    /* 分块上传时，每片的体积 */
                    chunk_size: 0,
                    /* 选择文件后自动上传，若关闭需要自己绑定事件触发上传 */
                    auto_start: true
                };

                /* 应用文件筛选器 */
                _default.filters = {
                    /* 只显示符合类型、大小的文件 */
                    mime_types: _mime_types,
                    max_file_size: _default.max_file_size,
                    prevent_duplicates: true
                };

                /**
                 * 初始化处理函数
                 * @type type
                 */
                var _init = {
                    FilesAdded: function (up, files) {
                    },
                    BeforeUpload: function (up, file) {
                        /* 每个文件上传前,处理相关的事情 */
                    },
                    UploadProgress: function (up, file) {
                        var html = '已上传 ' + file.percent + '%';
                        $self.html(html).val(html);
                    },
                    FileUploaded: function (up, file, info) {
                        /* 每个文件上传成功后,处理相关的事情
                         * 其中 info 是文件上传成功后，服务端返回的json，形式如
                         *  {
                         *    "hash": "Fh8xVqod2MQ1mocfI4S4KpRL6D98",
                         *    "key": "gogopher.jpg"
                         *  }
                         */
                        var domain = up.settings.domain;
                        var res = $.parseJSON(info);
                        var sourceLink = domain + res.key; //获取上传成功后的文件的Url
                        option.input.val(sourceLink).html(sourceLink).trigger('change');
                        $self.html($self.data('html')).val($self.data('html'));
                    },
                    Error: function (up, err, errTip) {
                        console.dir(arguments);
                    },
                    UploadComplete: function () {
                    },
                    Key: function (up, file) {
                        /* 若想在前端对每个文件的key进行个性化处理，可以配置该函数
                         * 该配置必须要在 unique_names: false , save_key: false 时才生效
                         */
                        var d = new Date();
                        var key = d.getFullYear() + '/';
                        key += (d.getMonth() + 1) + '/';
                        key += d.getDate() + '/';
                        key += d.getHours() + '/';
                        key += d.getMinutes() + '';
                        key += d.getSeconds() + '/';
                        key += file.name;
                        return key;
                    }
                };
                option = $.extend(_default, option || {});
                option.init = $.extend(_init, option.init || {});
                var uploader = Qiniu.uploader(option);
                $self.data('uploader', uploader);
                return uploader;
            };
            /**
             * 定时器
             */
            window.setInterval(function () {
                /* 单图片上传 */
                $('.upload_one_img:not("[loaded]"),[data-upload-one-img]:not("loaded")').map(function () {
                    var $this = $(this).attr('loaded', '');
                    $this.upload({file_type: 'jpg,png', file_desc: 'JPG,PNG文件'});
                });

                /* 上传文件 */
                $('.upload_one_file:not([loaded]),[data-upload-one-file]:not("loaded")').map(function () {
                    var $this = $(this).attr('loaded', '');
                    $this.upload();
                });

                /* 上传视频 */
                $('.upload_one_video:not([loaded]),[data-upload-one-video]:not("loaded")').map(function () {
                    var $this = $(this).attr('loaded', '');
                    $this.upload({file_type: 'mp4', file_desc: 'MP4文件'});
                });

                /* 多图片上传 */
                $('.upload_multi_img:not([loaded]),[data-upload-multi-img]:not("loaded")').map(function () {
                    var $this = $(this).attr('loaded', '');
                    $this.upload({file_type: 'jpg,png', file_desc: 'JPG,PNG文件', multi_selection: true});
                });
            }, 500);

        }});

});