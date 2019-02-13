<?php

$ak = 'JeT_3Lzo1Gs5BYL3H46gpbVcOsZyEVz9vMxrJLut';
$sk = 'HU_4VczEgDa9n4i7UuW2QJvlBGFsvM7b_VSAs0Xd';


$bucket = "wxshop";

include '../Qiniu.class.php';

$qiniu = new Qiniu($ak, $sk);

$uptoken = $qiniu->QiniuRSPutPolicy($bucket);

echo json_encode(array('uptoken' => $uptoken));
