<?php

/**
 * 启用插件后请在设置面板配置百度内容审核api，否则将拦截所有评论
 * @package CommentAuditor
 * @author ihesro
 * @version 1.0.7
 * @link http://www.galasp.cn
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
    $apiKey = new Typecho_Widget_Helper_Form_Element_Text('apiKey', null, null, _t('apiKey:'));
    $form->addInput($apiKey->addRule('required', _t('不能为空！')));

    $secretKey = new Typecho_Widget_Helper_Form_Element_Text('secretKey', null, null, _t('secretKey:'));
    $form->addInput($secretKey->addRule('required', _t('不能为空！')));
  }
  //主函数
  public static function audit($comment, $post)
  {
    //获取配置项
    $opt = Typecho_Widget::widget('Widget_Options')->plugin('CommentAuditor');
    $check = self::check($comment['text'] . $comment['author'], $opt->apiKey, $opt->secretKey);
    $res = array(
      "type" => @$check['conclusionType'] == 1 ? 'safe' : 'evil',
      "reason" => @isset($check['data'][0]['msg']) ? $check['data'][0]['msg'] : ''
    );
    if ($res['type'] == 'evil') {
      $comment['text'] .= "
        <font style='display: inline-block;box-shadow: 0 2px 5px rgba(0, 0, 0, 0.15);margin:20px 0px;padding:15px;border-radius:5px;font-size:14px;color:#000000;'>
          因昵称或评论内容
          &nbsp;*<font style='border-bottom: #C7254E 1.5px solid;'>" . $res['reason'] . "</font>*&nbsp;
          &nbsp;进入博主复核。——<a href='https://b.nit9.cn/archives/9.html' target='_blank'>CommentAuditor</a>
        </font>
      ";
      $comment['status'] = 'waiting';
    } else {
      $comment['status'] = 'approved';
    }
    return $comment;
  }



  //华丽配置面板
  public static function personalConfig(Typecho_Widget_Helper_Form $form)
  {
  }

  //以下代码为百度内容审核api
  protected function request($url, $data, $apiKey, $secretKey)
  {
    $params = array();
    $authObj = self::auth($apiKey, $secretKey);
    $params['access_token'] = $authObj['access_token'];
    $params['aipSdk'] = 'php';
    $params['aipSdkVersion'] = '2_2_17';
    $response = self::baiduRequest($url . "?" . http_build_query($params), $data, 1);
    $obj = json_decode($response['content'], true);
    return $obj;
  }

  public function auth($apiKey, $secretKey)
  {
    $response = self::baiduRequest(
      'https://aip.baidubce.com/oauth/2.0/token',
      array(
        'grant_type' => 'client_credentials',
        'client_id' => $apiKey,
        'client_secret' => $secretKey,
      )
    );
    $obj = json_decode($response['content'], true);
    return $obj;
  }

  private function baiduRequest($url, $params = "", $ispost = 0)
  {
    $params = is_array($params) ? http_build_query($params) : $params;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    if ($ispost) {
      curl_setopt($ch, CURLOPT_POST, true);
      curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
      curl_setopt($ch, CURLOPT_URL, $url);
    } else {
      if ($params) {
        curl_setopt($ch, CURLOPT_URL, $url . '?' . $params);
      } else {
        curl_setopt($ch, CURLOPT_URL, $url);
      }
    }
    $response = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if ($code === 0) {
      throw new \Exception(curl_error($ch));
    }

    curl_close($ch);
    return array(
      'code' => $code,
      'content' => $response,
    );
  }

  public function check($message, $apiKey, $secretKey)
  {
    $data = array();
    $data['text'] = $message;
    return self::request('https://aip.baidubce.com/rest/2.0/solution/v1/text_censor/v2/user_defined', $data, $apiKey, $secretKey);
  }
}
