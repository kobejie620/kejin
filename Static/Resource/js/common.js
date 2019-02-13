/**
 *  JS公共基础控制组件
 * --------------------------------
 *  此文件为系统核心，不允许对此文件修改
 * ================================
 *  依赖组件
 *  1. jQuery 1.72+
 *  2. layui(layer.m,laydate)
 * ================================
 * @version 2.0
 * @author zoujingli <zoujingli@qq.com>
 * @date 2015/04/14 10:19:54
 * ================================
 */


/**
 * 页面加载前期处理
 * @param {type} $
 * @returns {undefined}
 */
!(function ($) {

    "use strict";
    /**
     * 定义应用构造函数
     * @returns {common_L11._app}
     */
    function _app() {
        this.version = '2.0';
        this.author = 'zoujingli';
        this._RES_ = window._RES_ || function () {
            var a = document.scripts, b = a[a.length - 1].src;
            return  b.substring(0, b.lastIndexOf("/") - 2);
        }();
        this._LIB_ = window._LIB_ || function () {
            var a = document.scripts, b = a[a.length - 1].src;
            return  b.substring(0, b.lastIndexOf("/") - 11) + 'Plugins/';
        }();
        this.init();
    }

    /**
     * 应用初始化
     * @returns {undefined}
     */
    _app.prototype.init = function () {


        var self = this;

        /**
         * 加载剪切板控制插件
         */
        this.loadScript(this._LIB_ + '/qiniuue/third-party/zeroclipboard/ZeroClipboard.min.js', function () {
            ZeroClipboard.config({swfPath: self._LIB_ + '/qiniuue/third-party/zeroclipboard/ZeroClipboard.swf'});
            window.setInterval(function () {
                $('[data-copy]:not("[done]")').map(function () {
                    this.setAttribute('done', '');
                    var self = this;
                    var client = new ZeroClipboard($(this));
                    var text = $(this).attr('data-copy');
                    client.on('ready', function () {
                        client.on('copy', function (event) {
                            event.clipboardData.setData('text/plain', text);
                        });
                        client.on('aftercopy', function () {
                            $.msg.success($(self).attr('[data-success-tips]') || '内容复制成功！');
                            $(self).blur();
                        });
                    });
                    client.on('error', function () {
                        ZeroClipboard.destroy();
                    });
                });
            }, 500);
        });

        /**
         * 时间插件初始化
         */
        this.loadScript(this._LIB_ + '/layui/laydate/laydate.js', function () {
            window.setInterval(function () {
                $('input[data-date]:not([data-done])').map(function () {
                    this.setAttribute('data-done', '');
                    var id = this.id = '_date_' + Math.ceil(Math.random() * 1000000);
                    laydate({
                        elem: '#' + id,
                        format: 'YYYY-MM-DD',
                        istime: false,
                        istoday: true,
                        fixed: false
                    });
                });
                $('input[data-datetime]:not([data-done])').map(function () {
                    this.setAttribute('data-done', '');
                    var id = this.id = '_date_' + Math.ceil(Math.random() * 1000000);
                    laydate({
                        elem: '#' + id,
                        format: 'YYYY-MM-DD hh:mm:ss',
                        istime: true,
                        istoday: true,
                        fixed: false
                    });
                });
            }, 500);
        });
    };
    /**
     * 同步加载JS 带缓存
     * @param {type} url
     * @param {type} callback
     * @returns {undefined}
     */
    _app.prototype.loadScript = function (url, callback) {
        $.ajax({async: false, url: url, dataType: "script", cache: true, success: callback});
    };
    /**
     * 弹出层插件 FancyBox
     * @param {type} callback 回调代码
     */
    _app.prototype.fancy = function (callback) {
        if (typeof ($.fancybox) === 'undefined') {
            jQuery('<link rel="stylesheet" href="' + window._LIB_ + '/fancy/jquery.fancybox.css">').appendTo('body');
            jQuery('<link rel="stylesheet" href="' + window._LIB_ + '/fancy/helpers/jquery.fancybox-thumbs.css">').appendTo('body');
            jQuery('<link rel="stylesheet" href="' + window._LIB_ + '/fancy/helpers/jquery.fancybox-buttons.css">').appendTo('body');
            $.loadScript(window._LIB_ + '/fancy/jquery.mousewheel.pack.js');
            $.loadScript(window._LIB_ + '/fancy/helpers/jquery.fancybox-thumbs.js');
            $.loadScript(window._LIB_ + '/fancy/helpers/jquery.fancybox-media.js');
            $.loadScript(window._LIB_ + '/fancy/helpers/jquery.fancybox-buttons.js');
            $.loadScript(window._LIB_ + '/fancy/jquery.fancybox.js', callback);
        } else {
            callback();
        }
    };
    /**
     * 创建百度编辑器
     * @param {String} id 页面标签ID
     */
    _app.prototype.createEditor = function (id) {
        window.editor = window.editor || {};
        //如果对象已经定义，需要销毁对象
        if (window.editor[id]) {
            window.editor[id].destroy();
        }
        window.editor[id] = UE.getEditor(id);
    };
    /**
     * 定义消息处理构造方法
     * @returns {common_L11._msg}
     */
    function _msg() {
        this.version = '2.0';
    }

    /**
     * 显示消息框
     * @param {type} msg 消息内容
     * @param {type} icon 消息类型
     * @param {type} timeout(s) 消息显示时间
     * @param {type} callback 回调函数
     * @param {type} close 是否开启关闭按钮
     */
    _msg.prototype.show = function (msg, icon, timeout, callback) {
        layer.close(this.index);
        if (timeout === 'hide') {
            typeof callback === 'function' && callback.call();
        }
        return this.index = layer.open({
            content: this._getContentByIco(icon, msg || '处理中...'),
            time: timeout,
            end: callback,
            shadeClose: false,
            style: 'background:#fff;color:#666'
        });
    };
    /**
     * 关闭消息框
     * @param {type} timeout 延迟关闭时间
     */
    _msg.prototype.close = function () {
        return layer.close(this.index);
    };
    /**
     * 显示成功类型的消息
     * @param {type} msg 消息内容
     * @param {type} timeout  延迟关闭时间
     * @param {type} callback 回调函数
     */
    _msg.prototype.success = function (msg, timeout, callback) {
        this.show(msg, 1, timeout || 2, callback, false);
    };

    /**
     * 弹出警告消息框
     * @param {type} msg
     * @param {type} callback
     * @returns {undefined}
     */
    _msg.prototype.alert = function (msg, callback) {
        this.index = layer.open({
            content: '<div style="min-width:200px;text-align:center">' + msg + '</div>',
            shadeClose: false,
            btn: ['确认'],
            yes: callback
        });
    };
    /**
     * 确认对话框
     * @param {type} msg 提示消息内容
     * @param {type} ok 确认的回调函数
     * @param {type} no 取消的回调函数
     * @returns {undefined}
     */
    _msg.prototype.confirm = function (msg, ok, no) {
        this.index = layer.open({
            content: '<div style="min-width:200px;text-align:center">' + msg + '</div>',
            btn: ['确认', '取消'],
            shadeClose: false,
            yes: ok,
            no: no
        });
    };
    /**
     * 显示失败类型的消息
     * @param {type} msg 消息内容
     * @param {type} timeout 延迟关闭时间
     * @param {type} callback 回调函数
     */
    _msg.prototype.error = function (msg, timeout, callback) {
        this.show(msg, 3, timeout || 3, callback, false);
    };

    /**
     * 生成图片
     * @param {type} index
     * @returns {undefined}
     */
    _msg.prototype._getContentByIco = function (index, content) {
        if (index === undefined) {
            return content;
        }
        if (content && content !== '&nbsp;') {
            return '<span class="layer-ico layer-ico' + index + '"></span><span style="margin-left:38px">' + content + '</span>';
        } else {
            return '<span class="layer-ico layer-ico' + index + '"></span><span style="margin-left:20px"></span>';
        }
    };
    /**
     * 显示正在加载中的提示
     * @param {type} msg 提示内容
     */
    _msg.prototype.loading = function (msg, callback) {
        layer.close(this.index);
        return this.index = layer.open({
            content: this._getContentByIco(16, msg),
            end: callback,
            shadeClose: false,
            style: 'background:#fff;color:#666'// : 'background:none;text-align:center;color:#fff;box-shadow:none;border:none;'
        });
    };
    /**
     * 自动处理显示Think返回的Json数据
     * @param {type} data JSON数据对象
     * @param {type} time 延迟关闭时间
     */
    _msg.prototype.auto = function (data, time) {
        if (data.status === 1) {
            $.msg.success(data.info, time, function () {
                if (data.url) {
                    window.top.location.href = data.url;
                } else {
                    window.top.location.reload();
                }
            });
        } else {
            $.msg.error(data.info, 3);
        }
    };
    /**
     * 消息实例挂载
     */
    _app.prototype.msg = new _msg();

    /**
     * 表单构造函数
     * @returns {common_L11._form}
     */
    function _form() {
        this.version = '2.0';
        this.doing = false;
        this._model = null;
    }

    /**
     * 绑定表单
     * @param {type} form
     * @returns {unresolved}
     */
    _form.prototype._setlock = function (form) {
        this.doing = true;
        if (typeof form === 'object' && form.tagName === 'FORM') {
            $(form).attr('disabled', 'disabled');
        }
    };

    /**
     * 解锁表单
     * @param {type} form
     */
    _form.prototype._setUnlock = function (form) {
        this.doing = false;
        if (typeof form === 'object' && form.tagName === 'FORM') {
            $(form).removeAttr('disabled');
        }
    };

    /**
     * 判断表单是否锁定
     * @param {type} form
     * @returns {Boolean}
     */
    _form.prototype._islock = function (form) {
        if (this.doing) {
            return true;
        }
        if (typeof form === 'object' && form.tagName === 'FORM') {
            if ($(form).attr('disabled')) {
                return true;
            }
        }
        return false;
    };

    /**
     * 以任务进度加载
     * @param {string} url URL地址
     * @param {json|form|$form} data 额外请求数据
     * @param {type} type 提交的类型 GET|POST
     * @param {type} tips 初始提示内容
     * @param {type} timeout 成功后的停留时间
     * @returns {undefined}
     */
    _form.prototype.progress = function (url, data, type, tips, timeout) {
        var self = this;

        if (this._islock(data)) {
            return;
        }
        this._setlock(data);

        var pkey = '_progress_' + Math.floor(Math.random() * 1000000) + Math.floor(Math.random() * 1000000);
        $.msg.loading('<span class="' + pkey + '_txt">' + (tips || '') + '</span>');
        $('body').bind(pkey, function (e, data) {
            if (data.status === -1) {
                $('.' + pkey + '_txt').html(data.info);
            } else {
                $('[name="' + pkey + '"]').remove(), $('body').unbind(pkey), $.msg.auto(data, timeout);
                data.status ? self.doing = false : self._setUnlock(data);
            }
        });
        type = (type || "GET").toLocaleUpperCase();
        url += ((url.indexOf('?') !== -1 ? '&' : '?') + "progress=" + pkey);

        /* 写iframe容器对象 */
        $('<iframe style="height:1px;width:1px;overflow:hidden" src="javascript:void(0)"></iframe>').attr('name', pkey).appendTo('body').on('load', function () {
            $(this).add($form).remove();
        });
        var $form = $('<form data-validate-none></form>').attr('method', type).attr('target', pkey).attr('action', url);
        /* 如果data是form对象 */
        if (typeof data === 'object' && data.tagName === 'FORM') {
            $(data).find('input,select,textarea').map(function () {
                $(this).clone().appendTo($form).hide();
            });
        } else {
            if (type === 'GET') {
                data = $.extend(data || {}, {progress: pkey});
            }
            for (var i in data) {
                $('<input type="hidden"/>').attr('name', i).val(data[i]).appendTo($form);
            }
        }
        $form.appendTo('body').trigger('submit');
    };
    /**
     * 异步加载的数据
     * @param {type} url 请求的地址
     * @param {json|form|$form} data 额外请求数据
     * @param {type} type 提交的类型 GET|POST
     * @param {type} tips 提示消息
     * @param {type} tips 消息提示时间
     */
    _form.prototype.load = function (url, data, type, tips, timeout) {
        var self = this;

        if (this._islock(data)) {
            return;
        }
        this._setlock(data);

        $.msg.loading(tips);

        var send_data = {};
        if (typeof data === 'object' && data.tagName === 'FORM') {
            var send_data = $(data).serialize();
        } else {
            var send_data = data;
        }

        $.ajax({
            type: type || 'GET',
            url: url,
            data: send_data || {},
            statusCode: {
                404: function () {
                    $.msg.error('服务器繁忙，请稍候再试！ [404]');
                    self._setUnlock(data);
                }
            },
            error: function (XMLHttpRequest, textStatus, errorThrown) {
                /* Ajax请求出现错误时，友好提示用户，并显示错误状态消息 */
                $.msg.error('服务器繁忙，请稍候再试！ [ ' + textStatus + ', ' + errorThrown + ' ]');
                self._setUnlock(data);
            },
            success: function (res) {
                $.msg.close(0);
                /* 1、如果访问的页面没有权限或某些执行类操作时会反回标准的JSON数据 */
                if (typeof (res) === 'object') {
                    $.msg.auto(res, timeout);
                    self.doing = false;
                    res.status ? self.doing = false : self._setUnlock(data);
                    return;
                } else {
                    self._setUnlock(data);
                }
                /* 2、如果系统出现致命信息错误时，内容中呆以包涵以下信息 */
                if (res.indexOf('系统发生错误') !== -1) {
                    $.msg.error('服务器繁忙, 请稍候再试！');
                    return;
                }
                /* HTML数据加载成功后显示到模态框中 */
                self._modal = jQuery(res).appendTo('body').modal('show').on('hidden.bs.modal', function () {
                    $(this).remove();
                    self._modal = null;
                });

            }
        });
    };
    /**
     * 关闭FORM框
     * @returns {undefined}
     */
    _form.prototype.close = function () {
        $(this._modal).modal('hide');
    };
    /**
     * 表单实例挂载
     */
    _app.prototype.form = new _form();
    /**
     * 列表构造函数
     * @returns {common_L11._list}
     */
    function _list() {
        this.version = '2.0';
    }

    /**
     * 选择当前列表的数据项
     * @param {type} obj 当前CHECKBOX
     * @param {type} id 列表ID
     * @param {type} name CHEKBOX的名称
     */
    _list.prototype.selectAll = function (obj, id, name) {
        $('#' + id + ' tbody input[name=' + name + ']').each(function () {
            this.checked = !!obj.checked;
        });
    };
    /**
     * 获取列表中勾选的数据项目
     * @param {type} key 列表KEY
     * @returns {array|boolean}
     */
    _list.prototype.getSelectedId = function (key) {
        var inputs = $('table tbody tr td input[name=' + (key || 'key') + ']:checked');
        if (inputs.size() < 1) {
            $.msg.show('请选择需要操作的数据？', 'info', 2);
            return false;
        }
        var ids = [];
        inputs.map(function () {
            ids.push(this.value);
        });
        return ids.join(',');
    };
    /**
     * 列表实例挂载
     */
    _app.prototype.list = new _list();
    /**
     * 应用实例挂载
     */
    $.extend(new _app());
})(jQuery);

/**
 * 页面加载后期处理
 * @param {type} $
 * @returns {undefined}
 */
$(function () {
    try {
        /**
         * Validate 表单Ajax扩展
         * @author zoujingli <zoujingli@qq.com>
         */
        $.validator.setDefaults({
            // 通过验证后运行的函数
            submitHandler: function (form) {
                var $form = $(form);
                if ($form.data('ajax') === true) {
                    var url = $form.attr('action');
                    // 通用Ajax提交数据
                    if ($form.attr('data-progress')) {
                        $.form.progress(url, form, $form.attr('method'), $form.data('tip') || '正在提交数据，请稍候...', $form.data('timeout'));
                    } else {
                        $.form.load(url, form, $form.attr('method'), $form.data('tip') || '正在提交数据，请稍候...', $form.data('timeout'));
                    }
                    return false;
                } else {
                    // 普通数据
                    form.submit();
                }
            }
        });
        /* 表单检测处理 */
        $('form:not([novalidate]):not([data-validate-none])').map(function () {
            $(this).validate();
        });

        /* 表单检测处理 */
        window.setInterval(function () {
            $('form:not([novalidate]):not([data-validate-none])').map(function () {
                $(this).validate();
            });
        }, 500);
    } catch (e) {
    }

    /**
     * 事件委派
     */
    $('body>*')
            /**
             * 监听弹出层Modal标签
             * @returns {undefined}
             */
            .on('click', '[data-modal]', function () {
                $.form.load($(this).attr('data-modal'), {}, 'GET', $(this).attr('data-tips'));
            })

            /**
             * 侦听form加载组件
             */
            .on('click', '[data-load]', function () {
                $.form.load($(this).attr('data-load'), {}, 'GET', $(this).attr('data-tips'));
            })

            /**
             * 进程处理函数
             * @returns {undefined}
             */
            .on('click', '[data-progress]', function () {
                if (!$(this).is('form')) {
                    $.form.progress($(this).attr('data-progress'), {}, 'GET', $(this).attr('data-tips'));
                }
            })

            /**
             * 链接跳转
             * @returns {undefined}
             */
            .on('click', '[data-href]', function () {
                var href = $(this).attr('data-href');
                if (href && href.indexOf('#') !== 0) {
                    window.location.href = href;
                }
            })
            /**
             * 监听删除操作标签
             * @returns {undefined}
             */
            .on('click', '[data-del]', function () {
                var path = $(this).data('path');
                var id = $(this).data('del') || $.list.getSelectedId();
                if (id === false) {
                    return;
                }
                $.msg.confirm('确定要删除内容吗？', function () {
                    $.form.load(window._APP_ + '?s=' + path, {id: id}, 'POST', $(this).attr('data-tips'));
                });
            })

            /**
             * 禁用数据操作标签
             * @returns {undefined}
             */
            .on('click', '[data-forbid]', function () {
                var path = $(this).data('path');
                var id = $(this).data('forbid');
                $.form.load(window._APP_ + '?s=' + path, {id: id, status: 1}, 'POST', $(this).attr('data-tips'));
            })

            /**
             * 启用数据操作标签
             * @returns {undefined}
             */
            .on('click', '[data-resume]', function () {
                var path = $(this).data('path');
                var id = $(this).data('resume');
                $.form.load(window._APP_ + '?s=' + path, {id: id, status: 2}, 'POST', $(this).attr('data-tips'));
            })

            /**
             * 登出系统
             * @returns {undefined}
             */
            .on('click', '[data-logout]', function () {
                confirm('确定要退出登录吗？') && $.form.load(window._APP_ + '/admin/public/loginOut');
            })

            /**
             * 全选列表中指定的checkbox
             * @returns {undefined}
             */
            .on('click', 'input:checkbox[data-checked-all]', function () {
                var self = this;
                var id = $(self).data('checked-all');
                var name = $(self).data('checked-name');
                $('#' + id + ' tbody input[name=' + (name || 'key') + ']').each(function () {
                    this.checked = !!self.checked;
                });
            });


});

