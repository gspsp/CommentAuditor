<?php

/**
 * 评论审核员
 *
 * @package CommentAuditor
 * @author ihesro
 * @version 1.0.3
 * @link https://index.php/cross.html
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
    //ReadMe
    $ReadMe = new Typecho_Widget_Helper_Form_Element_Select('ReadMe', array('无视我,往下看'), '1', 'ReadMe', '<strong style="color: red;"><a href="https://b.nit9.cn/archives/9.html" target="_blank">食用方法</a>-尽情享用吧！</strong>');
    $form->addInput($ReadMe);
    //内容检测API配置
    $TextCheckApi = new Typecho_Widget_Helper_Form_Element_Text('TextCheckApi', null, null, _t('内容检测API:'));
    $form->addInput($TextCheckApi->addRule('required', _t('不能为空！')));
  }
  //主函数
  public static function audit($comment, $post)
  {
    //获取配置项
    $opt = Typecho_Widget::widget('Widget_Options')->plugin('CommentAuditor');
    $res=json_decode(self::fetch($opt->TextCheckApi . $comment['text'] . $comment['author']), true);
    if ($res['type'] == 'evil') {
      $comment['text'] .= "
        <font style='display: inline-block;box-shadow: 0 2px 5px rgba(0, 0, 0, 0.15);margin:20px 0px;padding:15px;border-radius:5px;font-size:14px;color:#000000;'>
          您的昵称或评论内容
          &nbsp;*<font style='border-bottom: #C7254E 1.5px solid;'>".$res['reason']."</font>*&nbsp;
          &nbsp;已丢给博主复核。——<a href='https://b.nit9.cn/archives/9.html' target='_blank'>CommentAuditor</a>
        </font>
      ";
      $comment['status'] = 'waiting';
    } else {
      $comment['status'] = 'approved';
    }
    return $comment;
  }


  /**function */
  public static function fetch($url)
  {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, "Dalvik/1.6.0 (Linux; U; Android 4.1.4; DROID RAZR HD Build/9.8.1Q-62_VQW_MR-2)");
    curl_setopt($ch, CURLOPT_REFERER, "-");
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    $str = curl_exec($ch);
    curl_close($ch);
    return $str;
  }
  //华丽配置面板
  public static function personalConfig(Typecho_Widget_Helper_Form $form)
  {
  }
}
