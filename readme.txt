    
    《 WX开发平台参考文档 》

一、Tag标签 --- table

    可设置的属性有：order,id,pk,class,color,bgcolor,action,show,sort,list,name,checkbox,length  

    1. order    false   设置是否使用排序功能，依赖于模型的sort字段
    2. id       随机     设置表格HTML的ID，方便JS操作表格，可以不设置
    3. pk       id      表格数据对应模块的主键，可以不设置
    4. class    boot    设置表格的CSS样式类
    5. color    空      设置表格的行颜色，可以使用PHP回调函数，如 字段名|PHP函数名，返回颜色值方可
    6. bgcolor  空      设置表格行的背景颜色，具体规则同上
    7. sort     false   是否开启表格列排序功能
    8. name     vo      数据生成时的中间变量
    9. check    true    设置表格左侧是否显示筛选框
    10.length   无限制   指定数据显示最大长度，默认无限制，可以设置整型数字加以控制

    11.show             列表显示的规则定义（PATH:系统程序节点、URL：外部访问地址）
        # 字段#PATH
        # 字段:名称:对齐#PATH        -> SHOW 字段值 
        # 字段|PHP函数:名称:对齐#PATH -> CALL_PHP函数(字段值,全记录数组,名称,PATH)
        # 字段:JS函数:名称:对齐#PATH  -> CALL_JS函数(字段值,全记录对象,名称,PATH);

    12.action       列表控制的规则定义：（PATH:服务器节点访问位置，PATH无访问权限时将不显示按钮）
        # 字段#PATH
        # 字段:名称:对齐#PATH        -> SHOW 字段值 
        # 字段|PHP函数:名称:对齐#PATH -> CALL_PHP函数(字段值,全记录数组,名称,PATH)
        # 字段:JS函数:名称:对齐#PATH  -> CALL_JS函数(字段值,全记录对象,名称,PATH);
        
        PHP协作函数
            show_href_button
            show_load_button
            show_open_button
            show_del_button
            show_status_button

二、后台取消JS调用机制，直接使用HTML元素规则委派操作
    
    【常用组件】

        data-copy=#text     复制操作
        data-date           实例日期控件
        data-datetime       实例日期时间控件
        data-editor         富文本调用
        img.fancy           图片弹出层显示
        

    【URL请求操作】

        data-href=#url                      在当前窗口打开页面，标题为元素的文本内容值
        data-modal=#url                     点击时使用modal加载对应URL内容并显示
        data-progress=#url                  实时进度显示，需要配置主控制中的 progress 方法使用
        data-forbid=#id data-path=PATH      禁用指定ID的数据，POST提交ID至PATH对应的URL，调用$.msg.auto处理返回的结果
        data-resume=#id data-path=PATH      启用指定ID的数据，POST提交ID至PATH对应的URL，调用$.msg.auto处理返回的结果
        data-del data-path=PATH             删除指定ID的数据，POST提交ID至PATH对应的URL，调用$.msg.auto处理返回的结果
        data-logout                         退出登录
        

    【文件上传操作】

        .upload_one_file    或  [data-upload-one-file]   
            --- 绑定单文件上传组件，data-type 设置文件类型,data-name绑定input

        .upload_one_img     或  [data-upload-one-img]    
            --- 绑定单图片上传组件，返回上传的结果至 data-name绑定的input中，同时触发onchange事件

        .upload_multi_img   或  [data-upload-multi-img]  
            --- 绑定多图片上传组件，返回上传的结果至data-name绑定的input中，同时触发onchange事件

        .upload_one_video   或  [data-upload-one-video]  
            --- 绑定视频上传组件，返回上传的结果至data-name绑定的input中，同时触发onchange事件  

三、常用JS库函数及参数
    A. jQuery.msg 消息库
        1. jQuery.msg.show(内容,函数,时间,类型);    // 显示消息内容
        2. jQuery.msg.auto(JSON,时间);            // 自动处理并显示消息内容
        3. jQuery.msg.success(内容,函数,时间);      // 显示成功图标类型的消息内容
        4. jQuery.msg.error(内容,函数,时间);        // 显示失败图标类型的消息内容
        5. jQuery.msg.close(时间,函数);            // 关闭消息提示柜
        6. jQuery.form.load(链接地址,数据,请求类型,提示消息); // 加载并自动显示内容
        7. jQuery.msg.confirm(内容,OK_函数,CANCEL_函数)
        8. jQuery.msg.alert(内容,OK_函数)
    B. jQuery.editor(id) 富文本
        


四、系统组件调整

    A. Excel导出操作
        
        # 实现Excel操作对象
        $excel = new \Wx\Api\Excel();

        # 应用数据并执行下载操作
        $excel->renderData($data)->download('短信历史记录');

        *** 数据组合应用 ***

        数据支持两种方式：
            1. 一维数据，需要带表格坐标Key值，
                    如 array('A1'=>value1,'B1'=>'value2');
               主要应用于基于Excel模板数据打点写值

            2. 二组数据，其实每个子数组代表一行记录，
                    如 array(array('value1','value2','value3'),array('value11','value22','value33'));
       
        *** 数据格式应用 *** 

            在以上两种数据格式中，值value可以是一个数组，比如：value1可以是 array(value,format);
            第一个元素代表单元格的内容值，第二个元素代表单元格格式
            其中格式（字符串）可以有：size:16;width:30;font:宋体;text-align:center;font-weight:bold;height:30px;vertical-align:center;type:string;full:ff66ff
            与CSS写法相似。

    B. Excel导入操作

       # 实现Excel操作对象
       $excel = new \Wx\Api\Excel();

       # 读取Excel文件,返回二维数组
       $excel->readerExcel($filename,$sheetIndex)


五、主控制器方法回调时序
    1、列表显示
        index   -> _search();
                        ->_filter($model,$map);
                        ->_ACTION_NAME_filter($model,$map);
                -> _list();
                        ->_list_filter();
                        ->_ACTION_NAME_list_filter($model,$map);
                        ->_data_filter($data,$model);
                        ->_ACTION_NAME_data_filter($data,$model);
                        
    2、添加操作
        add     -> _form_filter($model,$vo);
                -> _ACTION_filter($model,$vo);
                ====
                -> _form_success($model,$vo);
                -> _ACTION_NAME_success($model,$vo);
                ----
                -> _form_error($model,$vo);
                -> _ACTION_NAME_error($model,$vo);

    3、添加操作
        edit     -> _form_filter($model,$vo);
                -> _ACTION_NAME_filter($model,$vo);
                ====
                -> _form_success($model,$vo);
                -> _ACTION_NAME_success($model,$vo);
                ----
                -> _form_error($model,$vo);
                -> _ACTION_NAME_error($model,$vo);

    4、删除操作
        del     -> _ACTION_NAME_filter($model,$vo);
                ====
                -> _ACTION_NAME_success($model,$vo);
                ----
                -> _ACTION_NAME_error($model,$vo);

    5、禁用操作
        forbid  -> _ACTION_NAME_filter($model,$vo);
                ====
                -> _ACTION_NAME_success($model,$vo);
                ----
                -> _ACTION_NAME_error($model,$vo);

    6、启用操作
        resume  -> _ACTION_NAME_filter($model,$vo);
                ====
                -> _ACTION_NAME_success($model,$vo);
                ----
                -> _ACTION_NAME_error($model,$vo);


      