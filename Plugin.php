<?php

/**
 * 启用插件后请在设置面板配置百度内容审核api地址，否则将拦截所有评论
 * @package CommentAuditor
 * @author 食用教程
 * @version 1.0.5
 * @link https://b.saytf.cn/index.php/archives/7/
 */
class CommentAuditor_Plugin implements Typecho_Plugin_Interface
{
  //启用时
  public static function activate()
  {
    Typecho_Plugin::factory('Widget_Feedback')->comment = array(__CLASS__, 'audit');
    return _t("activated");
  }
  //禁用时
  public static function deactivate()
  {
    return _t("deactivated");
  }
  //设置面板
  public static function config(Typecho_Widget_Helper_Form $form)
  {
    $apiUrl = new Typecho_Widget_Helper_Form_Element_Text('apiUrl', null, null, _t('apiUrl:'));
    $form->addInput($apiUrl->addRule('required', _t('不能为空！')));
  }
  //主函数
  public static function audit($comment, $post)
  {
    //获取配置项
    $opt = Typecho_Widget::widget('Widget_Options')->plugin('CommentAuditor');
    $d = self::fetch($opt->apiUrl, 'POST', array(
      'text' => $comment['text'] . $comment['author']
    ));
    $r = json_decode($d['res'], true);
    if ($r['type'] != 1) {
      $comment['status'] = 'waiting';
    }
    return $comment;
  }


  public static function fetch($url, $type = 'GET', $params = array())
  {
    $params = http_build_query($params);
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, "Dalvik/1.6.0 (Linux; U; Android 4.1.4; DROID RAZR HD Build/9.8.1Q-62_VQW_MR-2)");
    curl_setopt($ch, CURLOPT_REFERER, "-");
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

    switch ($type) {
      case 'GET':
        curl_setopt($ch, CURLOPT_URL, $url . '?' . $params);
        break;
      case 'POST':
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        curl_setopt($ch, CURLOPT_URL, $url);
        break;
      case 'PUT':
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($ch, CURLOPT_URL, $url);
    }

    $res = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return array(
      'code' => $code,
      'res' => mb_convert_encoding($res, 'utf-8', 'GBK,UTF-8,ASCII'),
    );
  }

  //华丽配置面板
  public static function personalConfig(Typecho_Widget_Helper_Form $form)
  {
  }
}
