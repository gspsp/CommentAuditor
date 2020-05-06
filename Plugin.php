<?php
/**
 * 评论自动审核-百度内容审核版
 *
 * @package CommentAuditor
 * @author ihesro
 * @version 1.0.2
 * @link https://b.nit9.cn/index.php/cross.html
 */
class CommentAudit_plugin implements Typecho_Plugin_Interface{
  //启用时
  public static function activate(){
    Typecho_Plugin::factory('Widget_Feedback')->comment = array(__CLASS__, 'audit');
    return _t("activated");
  }
  //禁用时
  public static function deactivate(){
    return _t("deactivated");
  }
  //设置面板
  public static function config(Typecho_Widget_Helper_Form $form){
    //ReadMe
    $ReadMe=new Typecho_Widget_Helper_Form_Element_Select('ReadMe',array('无视我,往下看'),'1','ReadMe','<strong style="color: red;"><a href="//b.nit9.cn" target="_blank">食用方法</a>-尽情享用吧！</strong>');
    $form->addInput($ReadMe);
    //百度内容检测配置项
    $AppId=new Typecho_Widget_Helper_Form_Element_Text('AppId',null,null,_t('AppId:'));
    $AppKey=new Typecho_Widget_Helper_Form_Element_Text('AppKey',null,null,_t('AppKey:'));
    $SecretKey=new Typecho_Widget_Helper_Form_Element_Text('SecretKey',null,null,_t('SecretKey:'));
    $form->addInput($AppId->addRule('required', _t('不能为空！')));
    $form->addInput($AppKey->addRule('required', _t('不能为空！')));
    $form->addInput($SecretKey->addRule('required', _t('不能为空！')));
    //百度api配置项
    $ApiKey=new Typecho_Widget_Helper_Form_Element_Text('ApiKey',null,null,_t('ApiKey:'));
    $form->addInput($ApiKey->addRule('required', _t('不能为空！')));
  }
  //华丽配置面板
  public static function personalConfig(Typecho_Widget_Helper_Form $form){}
  
  public static function audit($comment, $post){
    //获取配置项
    $opt=Typecho_Widget::widget('Widget_Options')->plugin('CommentAudit');
    //加载api
    include 'AipBase.php';
    $cli=new Luffy\TextCensor\AipBase($opt->AppId,$opt->AppKey,$opt->SecretKey);
    //检测评论安全
    $commentIsSafe['text']=self::TextIsSafe($cli->textCensorUserDefined($comment['text'])['conclusionType']);
    //检测昵称安全
    $commentIsSafe['author']=self::TextIsSafe($cli->textCensorUserDefined($comment['author'])['conclusionType']);
    //检测链接安全
    $commentIsSafe['url']=self::UrlIsSafe($comment['url'],$opt->ApiKey);
    //调出不安全信息
    $commentUnsafeInfo=self::CommentUnsafeInfo($commentIsSafe);
    if(!$commentUnsafeInfo==null){
      $comment['text'].="
        <font style='display: inline-block;background: #fafafa repeating-linear-gradient(-45deg,#fff,#fff 1.125rem,transparent 1.125rem,transparent 2.25rem);box-shadow: 0 2px 5px rgba(0, 0, 0, 0.15);margin:20px 0px;padding:15px;border-radius:5px;font-size:14px;color:#555555;'>
          $commentUnsafeInfo
          &nbsp;疑似违规，已丢给博主复核。——CommentAuditor
        </font>
      ";
      $comment['status']='waiting';
    }else{
      $comment['status']='approved';
    }
    return $comment;
  }


  /**function */
  public static function GetWebText($url){
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
  public static function UrlIsSafe($link,$apiKey){
    $apiUrl='http://api.anquan.baidu.com/bsb/lookup?apikey='.$apiKey.'&url=';
    $res=json_decode(self::GetWebText($apiUrl.$link));
    $urlIsSafe=in_array($res->result[0]->main,[0,1,2]);
    return $urlIsSafe;

  }
  public static function TextIsSafe($res){
    //加载api // 1.合规，2.不合规，3.疑似，4.审核失败
    return $res=='1';
  }
  public static function CommentUnsafeInfo($commentIsSafe){
    $res=null;
    $err=array(text=>"评论内容",author=>"您的昵称",url=>"您的链接");
    foreach($commentIsSafe as $key=>$val){
      if(!$val){
        $res.=$res!=null?'&':null;
        $res.="&nbsp;*<font style='border-bottom: #C7254E 1.5px solid;'>".$err[$key]."</font>*&nbsp;";
      }
    }
    return $res;
  }
}