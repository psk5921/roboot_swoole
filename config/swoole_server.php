<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

use think\facade\Env;
use think\Db;
use app\api\model\User as UserModel;
use think\facade\Cache;
// +----------------------------------------------------------------------
// | Swoole设置 php think swoole:server 命令行下有效
// +----------------------------------------------------------------------
$pdo = '';
return [
    // 扩展自身配置
    'host'         => '0.0.0.0', // 监听地址
    'port'         => 9800, // 监听端口
    'type'         => 'socket', // 服务类型 支持 socket http server
    'mode'         => '', // 运行模式 默认为SWOOLE_PROCESS
    'sock_type'    => '', // sock type 默认为SWOOLE_SOCK_TCP
    'swoole_class' => '', // 自定义服务类名称

    // 可以支持swoole的所有配置参数
    'daemonize'    => false,
    'pid_file'     => Env::get('runtime_path') . 'swoole_server.pid',
    'log_file'     => Env::get('runtime_path') . 'swoole_server.log',

    // 事件回调定义
    'onOpen'       => function ($server, $request) {
        echo "server: handshake success with fd{$request->fd}\n";
    },

    'onMessage' => function ($server, $frame) {
        echo "receive from {$frame->fd}:{$frame->data},opcode:{$frame->opcode},fin:{$frame->finish}\n";
        $data = json_decode($frame->data,true);
        $UserModel = new UserModel;
        if(($id=$UserModel->uniqueByUser(htmlspecialchars($data['name']),false))){
            $fd = $frame->fd;
            Cache::set('fd_'.$frame->fd,$frame->fd,0);
            Cache::set('name_'.$fd,$id,0);
            Cache::set('time_'.$fd,$data['time'],0);
        }

        //$server->push($frame->fd, "this is server");
    },
    'onRequest' => function ($request, $response) {
        $response->end("<h1>Hello Swoole. #" . rand(1000, 9999) . "</h1>");
    },
    'onStart' =>function($server){
        swoole_set_process_name("psk");
    },
    'onWorkerStart'=>function($server, $worker_id){
         global     $pdo;
        echo 'workid:'.$worker_id.PHP_EOL;
       /* if(Cache::get('time')){
            foreach (Cache::get('time') as $timeid){
                $server->clearTimer($timeid);
            }
        }*/

        if ($server->worker_id == 0){
            $pdo = new PDO("mysql:host=localhost;dbname=robot_kedaweilai", "robot_kedaweilai", "YkKXY3AGZtLpE2t5");
            $server->tick(1000, function ($id) use($server){
                try {
                    global     $pdo;
                    $time[] = $id;
                    Cache::set('time',$time,0);
                    foreach ($server->connections as $fd) {
                        // 需要先判断是否是正确的websocket连接，否则有可能会push失败
                        if ($server->isEstablished($fd)) {
                            // echo  "当前fd {$fd} 是否活跃：".var_dump($server->isEstablished($fd)).PHP_EOL;
                            //echo  "当前时间fd是：".var_dump(Cache::get('time_'.$fd)).PHP_EOL;
                            //异步读取数据
                            // $where = ['uid'=>Cache::get('name_'.$fd),'create_at'=>['>',Cache::get('time_'.$fd)]];
                            // echo "where 条件：".var_dump($where).PHP_EOL;
                            // $order = 'create_at asc';
                            // $list = Db::name('content')->where($where)->order($order)->select();
                            //echo "数据存储的数据集 ：".var_dump($list).PHP_EOL;
                            $sql = "select * from robot_content where uid = '".Cache::get('name_'.$fd)."' and create_at > ".Cache::get('time_'.$fd) ." order by create_at asc";
                            //echo   $sql.PHP_EOL;
                            $stmt = $pdo->prepare($sql);
                            $stmt->execute();
                            $res = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            if ($res) {
                                foreach ($res as $k=>$items){
                                    $arr[$k]['content'] =  $items['content'];
                                    $arr[$k]['create_at'] = date('H:i',$items['create_at']);
                                    $arr[$k]['time'] = $items['create_at'];
                                }
                                $datas =  ['list'=>$arr,'time'=>$arr[count($arr)-1]['time']];
                                if ($server->isEstablished($fd)) {
                                    $server->push($fd, json_encode($datas));
                                }
                            }
                        }else{
                            echo "没得玩结束了".PHP_EOL;
                        }
                    }






                    /* if ($res[0]['is_update'] == 1) {
                         //当is_update字段为1时表明数据有更新，向客户端推送消息
                         $this->update();
                         //更新下表更新字段
                         $update = 'update is_update set is_update=0 where table_name = "swoole_test"';
                         $stmt = $this->pdo->prepare($update);
                         $stmt->execute();
                     }*/
                } catch (Exception $e) {
                    $pdo = new PDO("mysql:host=localhost;dbname=robot_kedaweilai", "robot_kedaweilai", "YkKXY3AGZtLpE2t5");
                }
            },$server);
           /* $server->tick(1000,function ($id) use($server){

            });*/
        }
    } ,
    'onClose' => function ($ser, $fd) {
        echo "client {$fd} closed\n";
        //清除缓存信息
        Cache::rm('fd_'.$fd);
        Cache::rm('time_'.$fd);
    },
];
