<?php
/**
 * Created by PhpStorm.
 * User: si
 * Date: 2017/7/21
 * Time: 20:04
 */

namespace App\Api\Server;


use App\common;
use App\Ticket;
use app\Wechat\WxPayNotify;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class WxServer extends WxPayNotify
{

    public function NotifyProcess($data, &$msg)
    {
        Storage::disk('local')->put('file1.txt',"支付完成调用了");

        if ($data['result_code'] == 'SUCCESS')
        {
            $tno = $data['out_trade_no'];
            $token = $this->getToken($tno);
            $t = Ticket::where(['tno'=>$tno])->first();
            Storage::disk('local')->put('t.txt',$t);
            if ($t) {
                Storage::disk('local')->put('t11.txt',$t);

                $t->token = $token;
                Storage::disk('local')->put('t22.txt',$t);
                $t->save();
                $this->getQrCode($tno,$t->token);
                $this->senMoMessage($t);
                Storage::disk('local')->put('file.txt',$t);
            }else{
                Storage::disk('local')->put('file.txt',' 没有 ');
            }
            Storage::disk('local')->put('token.txt',$token);

        }
        else
        {
            return true;
        }

    }

    public function getToken($tno){
        return encrypt('NumberSi0102' . $tno);
    }

    public function getQrCode($tno,$token)
    {
        // $path = public_path('qrcodes/' . $filesName . '.png');
        $picturedata=  QrCode::format('png')->size(250)->margin(1)->merge('/public/qrcodes/bus.jpg',.15)->generate($token);
        // $this->getImage($path);
        $disk = \Storage::disk('qiniu');
        $disk->put($tno.'.png',$picturedata);

    }

    public function senMoMessage($t)
    {

        $accessTokenServer= new AccessTokenServer();
        $url = 'https://api.weixin.qq.com/cgi-bin/message/wxopen/template/send?access_token='.$accessTokenServer->token;
        $params = [
            'touser' => $t->users->openid,
            'template_id' => 'IXLD8bxIF_YMjQ2cnJW1oIVSjVCXVVl50goeJhLqnLw',
            'page' => 'pages/me/me',
            'form_id' => $t->prepay_id,
            "data" => [
                "keyword1" => [
                    "value" => "沙集客运微信票",
                    "color" => "#173177"
                ],
                "keyword2" => [
                    "value" => $t->tno,
                    "color" => "#173177"
                ],
                "keyword3" => [
                    "value" => "请近日乘车,以免过期",
                    "color" => "#173177"
                ],
                "keyword4" => [
                    "value" => "每天最晚8点发车",
                    "color" => "#173177"
                ],
                "keyword5" => [
                    "value" => "上车请出票,请勿让他人获取二维码",
                    "color" => "#173177"
                ],
                "keyword6" => [
                    "value" => $t->money,
                    "color" => "#173177"
                ],
                "keyword7" => [
                    "value" => "乘车旅途中如果遇到问题,请拨打13737028118",
                    "color" => "#173177"
                ],
            ],

        ];
        $request = common::curl_post($url,$params);

    }

}