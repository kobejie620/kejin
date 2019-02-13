<?php

/**
 * 显示菜单操作按键
 * 
 * @param type $id
 * @param type $vo
 */
function show_menu_button($id, $vo) {
    return
            show_load_button($id, $vo, '添加子菜单', 'Admin/Menu/add') .
            show_load_button($id, $vo, '编辑', 'Admin/Menu/edit') .
            show_status_button(join(',', $vo['ids']), $vo, '') .
            show_del_button($id, $vo, '删除', 'Admin/Menu/del');
}
