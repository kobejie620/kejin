/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

$(function () {
    /**
     * 商品数量加减操作
     */
    $('.number').map(function () {
        $(this).find('a').eq(0).on('click', function () {
            var input = $(this).next('input');
            input.val(parseInt(input.val()) - 1).trigger('change');
        }).end().eq(1).on('click', function () {
            var input = $(this).prev('input');
            input.val(parseInt(input.val()) + 1).trigger('change');
        }).prev('input').on('change', function () {
            var max = $(this).data('max');
            this.value = parseInt(isNaN(this.value) || this.value < 1 ? 1 : this.value);
            this.value = this.value > max ? max : this.value;
        }).trigger('change');
    });
});




