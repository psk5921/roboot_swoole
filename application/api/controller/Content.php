<?php

namespace app\api\controller;

use app\api\consts\HttpCode;
use think\Controller;
use app\api\model\Content as ContentModel;
use app\api\model\User as UserModel;
class Content extends Controller
{
    /**
     * 发送消息
     * @param $access_token
     */
    public function send($access_token)
    {
        $content = input('content');
        if(!isset($access_token)){
            $res['msg'] = 'Miss Paragrams';
            $res['code'] = -1;
            return json($res, HttpCode::HTTP_CODE_FOR_202[0]);
        }
        $UserModel = new UserModel;
        if (!($uid=$UserModel->getUserByAccessToken($access_token))) {
            $this->json['msg'] = 'Invalid Access token';
            $this->json['code'] = -1;
            return json($this->json, HttpCode::HTTP_CODE_FOR_202[0]);
        }
        if(empty($content)){
            $res['msg'] = 'Miss Paragrams';
            $res['code'] = -1;
            return json($res, HttpCode::HTTP_CODE_FOR_202[0]);
        }
        if( mb_strlen($content) > 500 ) {
            $res['msg'] = 'OverPass Input String';
            $res['code'] = -1;
            return json($res, HttpCode::HTTP_CODE_FOR_202[0]);
        }
        $ContentModel = new ContentModel;
        $insert = [
            'uid' => $uid,
            'content' => htmlspecialchars($content),
        ];
        $res = $ContentModel->createContent($insert);
        return $res;
    }

    public function index(){
           return $this->fetch();
    }


    public function test() {
        return $this->fetch();
    }
}
