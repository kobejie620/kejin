/**
 * 后台初始化代码段
 * 
 * @author zoujingli <cxphp@qq.com>
 * @date 2014/09/03 18:35:24
 * @returns {undefined}
 */
$(function () {

    /**
     * 图片Tip显示 基于Bootstrap
     * @type String
     */
    $('.fancy').on('click', function () {
        var self = this;
        $.fancy(function () {
            try {
                var src = $(self).data('src').split('|');
            } catch (e) {
                var src = [self.src];
            }
            $.fancybox.open(src);
        });
    });

   
    /**
     * 后台菜单图标事件处理
     * @author anyon <cxphp@qq.com>
     * @returns {undefined}
     */
    $('.left-panel .left-panel-item>div').prev('a').attr('href', 'javascript:void(0)').on('click', function () {
        $(this).next('div').toggleClass('hide').end().find('i').toggleClass('glyphicon-chevron-down');
    }).parent('div').find('a').on('click', function () {
        if (this.href !== '#' && this.href.indexOf('javascript') !== 0) {
            $(this).addClass('active').siblings().removeClass('active').find('.active').removeClass('active');
            $(this).parent('div.sub-list-group').siblings('.active').removeClass('active');
        }
    });
});