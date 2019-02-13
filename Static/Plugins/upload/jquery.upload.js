jQuery(function () {

    /**
     * 文件上传jQuery插件
     * @returns {undefined}
     * @author zoujingli <zoujingli@qq.com>
     */
    jQuery.fn.upload = function (option) {

        /* 变量初始化 */
        option = option || {};
        var $self = jQuery(this);

        /* 事件触发按钮ID */
        option.id = option.id || $self.attr('id') || '_' + parseInt(Math.random() * 10000);
        $self.attr('id', option.id);

        /* 返回值接收ID */
        option.name = option.name || $self.data('name') || 'upload';
        $self.data('name', option.name);

        /**
         * 初始化参数
         * @type type
         */
        var _default = {
            multi_selection: true, //是否启用多文件上传
            runtimes: 'html5,flash,html4', //上传模式,依次退化
            browse_button: option.id, //上传选择的点选按钮，**必需**
            uptoken_url: 'uptoken.php', //Ajax请求upToken的Url，**强烈建议设置**（服务端提供）
            // downtoken_url: '/downtoken', // Ajax请求downToken的Url，私有空间时使用,JS-SDK将向该地址POST文件的key和domain,服务端返回的JSON必须包含`url`字段，`url`值为该文件的下载地址
            unique_names: false, // 默认 false，key为文件名。若开启该选项，SDK会为每个文件自动生成key（文件名）
            save_key: false, // 默认 false。若在服务端生成uptoken的上传策略中指定了 `sava_key`，则开启，SDK在前端将不对key进行任何处理
            domain: 'http://wxshop.qiniudn.com/', //bucket 域名，下载资源时用到，**必需**
            container: 'container', //上传区域DOM ID，默认是browser_button的父元素，
            max_file_size: '500mb', //最大文件体积限制
            flash_swf_url: 'Moxie.swf', //引入flash,相对路径
            max_retries: 3, //上传失败最大重试次数
            dragdrop: true, //开启可拖曳上传
            drop_element: 'container', //拖曳上传区域元素的ID，拖曳文件或文件夹后可触发上传
            chunk_size: 0, //'500mb', //分块上传时，每片的体积
            auto_start: true //选择文件后自动上传，若关闭需要自己绑定事件触发上传,
        };

        /**
         * 初始化处理函数
         * @type type
         */
        var _init = {
            FilesAdded: function (up, files) {
                plupload.each(files, function (file) {
                    /* 文件添加进队列后,处理相关的事情 */
                });
            },
            BeforeUpload: function (up, file) {
                /* 每个文件上传前,处理相关的事情 */
            },
            UploadProgress: function (up, file) {
                var msg = file.percent + '%';
                jQuery('[name="' + option.name + '"]').val(msg);
                /* 每个文件上传时,处理相关的事情 */
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
                var res = jQuery.parseJSON(info);
                var sourceLink = domain + res.key; //获取上传成功后的文件的Url
                jQuery('[name="' + option.name + '"]').val(sourceLink).trigger('change');
            },
            Error: function (up, err, errTip) {
                console.dir(arguments);
                /* 上传出错时,处理相关的事情 */
            },
            UploadComplete: function () {
                console.dir(arguments);
                /* 队列文件处理完毕后,处理相关的事情 */
            },
            Key: function (up, file) {
                /* 若想在前端对每个文件的key进行个性化处理，可以配置该函数
                 * 该配置必须要在 unique_names: false , save_key: false 时才生效
                 */
                var d = new Date();
                var key = d.getFullYear() + '';
                key += (d.getMonth() + 1);
                key += d.getDay();
                key += d.getHours();
                key += d.getMinutes();
                key += d.getSeconds();
                key += '/';
                key += file.name;
                return key;
            }
        };
        option = jQuery.extend(_default, option || {});
        option.init = jQuery.extend(_init, option.init || {});
        return Qiniu.uploader(option);
    };
});