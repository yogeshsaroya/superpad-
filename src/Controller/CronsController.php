<?php

declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link      https://cakephp.org CakePHP(tm) Project
 * @since     0.2.9
 * @license   https://opensource.org/licenses/mit-license.php MIT License
 */

namespace App\Controller;

use Cake\Mailer\Mailer;
use Cake\Mailer\TransportFactory;

/**
 * Static content controller
 * This controller will render views from templates/Pages/
 * @link https://book.cakephp.org/4/en/controllers/pages-controller.html
 */
class CronsController extends AppController
{

    public function beforeFilter(\Cake\Event\EventInterface $event)
    {
        // methods name we can pass here which we want to allow without login
        parent::beforeFilter($event);
        /* https://book.cakephp.org/4/en/controllers/components/authentication.html#AuthComponent::allow */
        $this->Auth->allow();
        $this->autoRender = false;
    }



    public function index()
    {
    }

    public function sendEmail()
    {
        $setting = $this->Data->get_settings();
        if (isset($setting['email_sender']) && isset($setting['email_address']) && isset($setting['email_password']) && isset($setting['email_host'])) {
            TransportFactory::setConfig('Manual', [
                'className' => 'Smtp', //'Debug',
                'tls' => true,
                'port' => 587, 'host' => $setting['email_host'], 'username' => $setting['email_address'], 'password' => $setting['email_password']
            ]);
            $mailer = new Mailer('default');
            $mailer->setTransport('Manual');

            $query = $this->EmailServers->find('all')->where(['EmailServers.status' => 0])->limit(10);
            $data = $query->all()->toArray();
            if (!empty($data)) {
                foreach ($data as $list) {
                    try {
                        $res = $mailer
                            ->setEmailFormat('both')
                            ->setFrom([$setting['email_address'] => $setting['email_sender']])
                            ->setTo($list->email_to)
                            ->setSubject($list->subject)
                            ->deliver($list->message);


                        $up_arr = ['id' => $list->id, 'status' => 1];
                        $saveData = $this->EmailServers->newEntity($up_arr, ['validate' => false]);
                        $this->EmailServers->save($saveData);
                        ec('<div style="color: green;">Email has been sent to ' . $list->email_to . '</div>');
                    } catch (\Throwable $th) {

                        $up_arr = ['id' => $list->id, 'status' => 3];
                        $saveData = $this->EmailServers->newEntity($up_arr, ['validate' => false]);
                        $this->EmailServers->save($saveData);
                        ec('<div style="color: red;"><b>Email has been failed to ' . $list->email_to . '</b></div>');
                    }
                }
            }
        }
        exit;
    }

    /*
    To update sale status ##  update_sales_status
    */
    public function updateSalesStatus()
    {
        ec(DATE);
        $query = $this->Projects->find()->where(['product_status IN' => ['Coming Soon', 'Whitelist Open', 'Whitelist Closed', 'Live Now', 'Sold Out']]);
        $data = $query->all();
        if (!$data->isEmpty()) {
            foreach ($data as $list) {
                $dateArr = [
                    'whitelist_starts' => $list->whitelist_starts, 'whitelist_ends' => $list->whitelist_ends,
                    'sale_starts' => $list->sale_starts, 'sale_ends' => $list->sale_ends, 'token_distribution_starts' => $list->token_distribution_starts
                ];
                ec($dateArr);
                /* Change status from comming soon to whitelist Open*/
                if ($list->product_status == 'Coming Soon' && !empty($list->whitelist_starts) && strtotime($list->whitelist_starts->format('Y-m-d H:i:s')) <= strtotime(DATE)) {
                    $list->product_status = 'Whitelist Open';
                    $this->Projects->save($list);
                    ec("Sale " . $list->title . " status has been changed to Whitelist Open");
                }
                /* Change status from whitelist open to whitelist Closed*/ elseif ($list->product_status == 'Whitelist Open' && !empty($list->whitelist_ends) && strtotime($list->whitelist_ends->format('Y-m-d H:i:s')) <= strtotime(DATE)) {
                    $list->product_status = 'Whitelist Closed';
                    $this->Projects->save($list);
                    ec("Sale " . $list->title . " status has been changed to Whitelist Closed");
                }
                /* Change status from whitelist Closed to whitelist end*/ elseif ($list->product_status == 'Whitelist Closed' && !empty($list->sale_starts) && strtotime($list->sale_starts->format('Y-m-d H:i:s')) <= strtotime(DATE)) {
                    $list->product_status = 'Live Now';
                    $this->Projects->save($list);
                    ec("Sale " . $list->title . " status has been changed to Live Now");
                }
                /* Change status from whitelist Closed to whitelist end*/ elseif ($list->product_status == 'Live Now' && !empty($list->sale_ends) && strtotime($list->sale_ends->format('Y-m-d H:i:s')) <= strtotime(DATE)) {
                    $list->product_status = 'Sold Out';
                    $this->Projects->save($list);
                    ec("Sale " . $list->title . " status has been changed to Sold Out");
                }
            }
        } else {
            ec("Sales not found");
        }
        exit;
    }


    function array_random($array, $number = null)
    {
        $requested = ($number === null) ? 1 : $number;
        $count = count($array);

        if ($requested > $count) {
            throw new \RangeException(
                "You requested {$requested} items, but there are only {$count} items available."
            );
        }

        if ($number === null) {
            return $array[array_rand($array)];
        }

        if ((int) $number === 0) {
            return [];
        }

        $keys = (array) array_rand($array, $number);

        $results = [];
        foreach ($keys as $key) {
            $results[] = $array[$key];
        }

        return $results;
    }



    /* Setp 1  //  mk_ticket
    Create tickets when sales status is Live Now and Applications status is 1
    */
    public function mkTicket()
    {
        $levels = $this->Levels->find()->order(['spad' => 'ASC'])->all();
        $tire = null;
        if (!$levels->isEmpty()) {
            foreach ($levels as $a) {
                if (!empty($a->spad)) {
                    $tire[$a->spad] = [
                        'spad' => $a->spad, 'title' => $a->title, 'ticket_multiplier' => $a->ticket_multiplier,
                        'cooldown' => $a->cooldown, 'social_task' => $a->social_task, 'max_ticket_allocation' => $a->max_ticket_allocation,
                        'winning_chances' => $a->winning_chances, 'guaranteed_allocation' => $a->guaranteed_allocation
                    ];
                }
            }
        }
        $min_tier = min(array_keys($tire));
        $max_tier = max(array_keys($tire));
        if ($min_tier <= 0) {
            die('Min Tier not found');
        }


        $data = $this->Projects->find()
            ->contain(['Applications' => ['conditions' => ['Applications.status' => 1], 'Users' => ['UserStakes']]])
            ->select(['id', 'title', 'product_status', 'status', 'sale_starts', 'sale_ends', 'token_required'])
            ->where(['Projects.token_required' => 1, 'Projects.product_status' => 'Whitelist Closed'])->all();

        $saveMany = $saveManyApp = [];
        if (!$data->isEmpty()) {
            foreach ($data as $list) {
                if (!empty($list['applications'])) {
                    foreach ($list['applications'] as $app) {
                        $token_bal = 0;
                        if (isset($app->user->user_stakes)) {
                            $token_bal = array_sum(array_column($app->user->user_stakes, 'balance'));

                            $closest_tier = null;
                            if (!empty($tire)) {
                                foreach ($tire as $a => $b) {
                                    if ($token_bal >= $a) {
                                        $closest_tier = $b;
                                    }
                                }
                                if ($closest_tier === null && $token_bal >= $max_tier) {
                                    $closest_tier = end($tire);
                                }
                            }
                            if (!empty($closest_tier)) {
                                $lot_no =  round(($token_bal / $min_tier) * $closest_tier['ticket_multiplier']);
                                if ((int)$lot_no > 0) {
                                    for ($i = 1; $i <= $lot_no; $i++) {
                                        $saveMany[] = ['id' => null, 'user_id' => $app->user->id, 'project_id' => $list->id, 'application_id' => $app->id, 'wallet_id' => $app->user->metamask_wallet_id, 'status' => 0];
                                    }
                                    $saveManyApp[] = [
                                        'id' => $app->id, 'status' => 2, 'actual_spad' => $token_bal,
                                        'spad' => $closest_tier['spad'], 'cooldown' => $closest_tier['cooldown'], 'social_task' => $closest_tier['social_task'],
                                        'max_ticket_allocation' => $closest_tier['max_ticket_allocation'], 'winning_chances' => $closest_tier['winning_chances'],
                                        'guaranteed_allocation' => $closest_tier['guaranteed_allocation']
                                    ];
                                    $app_ids[] = $app->id;
                                    ec("User " . $app->user->id . " allocated $lot_no tickets");
                                } else {
                                    $saveManyApp[] = [
                                        'id' => $app->id, 'status' => 3, 'actual_spad' => $token_bal,
                                        'spad' => $closest_tier['spad'], 'cooldown' => $closest_tier['cooldown'], 'social_task' => $closest_tier['social_task'],
                                        'max_ticket_allocation' => $closest_tier['max_ticket_allocation'], 'winning_chances' => $closest_tier['winning_chances'],
                                        'guaranteed_allocation' => $closest_tier['guaranteed_allocation']
                                    ];
                                    $app_ids[] = $app->id;
                                    ec("User " . $app->user->id . " does not enough ticket multiplier!");
                                }
                            } else {
                                $saveManyApp[] = ['id' => $app->id, 'status' => 3];
                                $app_ids[] = $app->id;
                                ec("User " . $app->user->id . " does not enough stake!");
                            }
                        } else {
                            ec("User " . $app->user->id . " does not have any stake!");
                        }
                    }
                } else {
                    ec('Applications not found!');
                }
            }


            if (!empty($saveMany)) {
                $tikEnt = $this->Tickets->newEntities($saveMany);
                $result = $this->Tickets->saveMany($tikEnt);
                //ec($result);
                ec('Tickets Saved');
            }
            if (!empty($saveManyApp)) {
                $chkApp = $this->Applications->find()->where(['id IN' => $app_ids])->all();
                $appEnt = $this->Applications->patchEntities($chkApp, $saveManyApp, ['validate' => false]);
                $res = $this->Applications->saveMany($appEnt);
                //ec($res);
                ec('Applications Saved');
            }
        } else {
            ec('Empty');
        }
        exit;
    }

    /* Setp 2  // mk_lottery
    do lottery when sales status is Live Now and Applications status is 2
    */
    public function mkLottery()
    {
        $data = $this->Projects->find()
            ->contain(['Applications' => ['conditions' => ['Applications.status' => 2], 'Tickets']])
            ->select(['id', 'title', 'product_status', 'status', 'sale_starts', 'sale_ends', 'total_raise', 'ticket_allocation', 'price_per_token', 'token_required'])
            ->where(['Projects.token_required' => 1, 'Projects.product_status' => 'Whitelist Closed'])->all();
        $saveTickets = $ticket_ids = [];
        if (!$data->isEmpty()) {
            foreach ($data as $projects) {
                if (!empty($projects->applications)) {
                    foreach ($projects->applications as $applications) {
                        $tot = count($applications->tickets);
                        $num = $tot;
                        if ($tot > $applications->max_ticket_allocation) {
                            $num = (int)$applications->max_ticket_allocation;
                        }

                        $rand_keys = array_rand($applications->tickets, $num);
                        if ($rand_keys === 0) {
                            $saveTickets[] = ['id' => $applications->tickets[0]->id, 'status' => 1];
                            $ticket_ids[] = $applications->tickets[0]->id;
                        } else {
                            foreach ($rand_keys as $k => $v) {
                                $saveTickets[] = ['id' => $applications->tickets[$v]->id, 'status' => 1];
                                $ticket_ids[] = $applications->tickets[$v]->id;
                            }
                        }

                        $this->Tickets->updateAll(['status' => 2], ['application_id' => $applications->id]);
                        if (!empty($saveTickets)) {
                            $applications->status = 4;
                            $applications->is_notified = 1;
                            $app_res = $this->Applications->save($applications);
                            $chkEnt = $this->Tickets->find()->where(['id IN' => $ticket_ids])->all();
                            $setEnt = $this->Tickets->patchEntities($chkEnt, $saveTickets, ['validate' => false]);
                            $res = $this->Tickets->saveMany($setEnt);
                            ec('Tickets Saved for application id ' . $applications->id);
                        }
                    }
                } else {
                    ec('No applications found');
                }
            }
        } else {
            ec('empty');
        }

        exit;
    }

    /* Setp 3 // lottery_noti
    send email to those selected for lottery 
    */
    public function lotteryNoti()
    {
        $data = $this->Applications->find()
            ->contain(['Users'])
            ->where(['Applications.status' => 4, 'Applications.is_notified' => 1])->all();
        if (!$data->isEmpty()) {
            foreach ($data as $list) {
                $this->Data->AppMail($list->user->email, 13, ['NAME' => $list->user->first_name]);
                $list->is_notified = 2;
                $this->Applications->save($list);
                ec("Lottery noti sent to user " . $list->user->email);
            }
        } else {
            ec('empty');
        }
        exit;
    }

    /* Setp 4  // set_tokens
        Set number of tokens when sales status is sold out and application joined_usd is >0 
        */
    public function setTokens()
    {
        $data = $this->Applications->find()->contain(['Projects' => ['Blockchains']])->select()
            ->where([
                'Applications.total_token' => 0, 'Applications.joined_usd >' => 0, 'Applications.status' => 4,
                'Projects.product_status' => 'Sold Out', 'Projects.price_per_token >' => 0
            ])
            ->all();
        if (!$data->isEmpty()) {
            foreach ($data as $list) {
                $tokens = $list->joined_usd / $list->project->price_per_token;
                if ($tokens > 0) {
                    $list->total_token = $tokens;
                    $list->available_token = $tokens;
                    $list->claimed_token = 0;
                    $this->Applications->save($list);
                    ec($tokens . " added for Application ID - " . $list->id);
                }
            }
        } else {
            ec('empty');
        }
        exit;
    }

    /* Setp 5  //  up_appliation
    update apllocation if sale is not requried staking
    */
    public function upAppliation()
    {
        $data = $this->Projects->find()
            ->contain(['Applications' => ['conditions' => ['Applications.status' => 1]]])
            ->select(['id', 'title', 'product_status', 'status', 'sale_starts', 'sale_ends', 'total_raise', 'ticket_allocation', 'price_per_token', 'max_allocation', 'token_required', 'max_allocation'])
            ->where(['Projects.token_required' => 2, 'Projects.max_allocation >' => 0, 'Projects.product_status' => 'Whitelist Closed'])->all();
        if (!$data->isEmpty()) {
            foreach ($data as $projects) {
                if (!empty($projects->applications)) {
                    foreach ($projects->applications as $applications) {

                        $applications->status = 4;
                        $applications->is_notified = 2;
                        $app_res = $this->Applications->save($applications);
                        ec('Applications update id ' . $applications->id);
                    }
                } else {
                    ec('No applications found');
                }
            }
        } else {
            ec('empty');
        }

        exit;
    }

    public function getAirdrops()
    {
        $path = 'airdrops';
        if (!file_exists($path)) {
            mkdir($path, 0777, true);
        }
        $data = $this->Airdrops->find('all', ['order' => ['Airdrops.id' => 'DESC']])->all();
        if (!$data->isEmpty()) {
            $header_row = ['ID', 'twitter', 'telegram', 'wallet_address'];
            $fname = 'airdrops.csv';
            $csv_file = fopen("airdrops/" . $fname, 'w');
            //fprintf($csv_file, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($csv_file, $header_row);
            foreach ($data as $record) {
                $row = [];
                $row = [$record->id, $record->twitter, $record->telegram, $record->wallet_address];
                fputcsv($csv_file, $row);
            }
            fclose($csv_file);
            ec(SITEURL . "airdrops/" . $fname);
        }
        exit;
    }

    public function checkClaims()
    {
        $data = $this->fetchTable('Claims')->find('all')
            ->where(['Claims.transaction_status' => 2, 'Claims.transaction_id !=' => ''])
            ->contain(['Applications'])
            ->first();
        if (!empty($data)) {
            $url = env('bscscanAPI') . 'api?module=transaction&action=gettxreceiptstatus&txhash=' . $data->transaction_id . '&apikey=' . env('bscscanKey');
            $res = $this->Data->fetch($url);
            if (!empty($res)) {
                $arr = json_decode($res, true);
                if ($arr['status'] == 1) {
                    if ($arr['result']['status'] == 1) {

                        $total_claimed_token =  $data->application->claimed_token + $data->total_token;
                        $available_token = $data->application->available_token - $data->total_token;
                        $data->application->claimed_token = $total_claimed_token;
                        $data->application->available_token = $available_token;

                        $data->transaction_status = 3;
                        $data->claimed_date = DATE;
                        $this->fetchTable('Claims')->save($data);
                        $this->fetchTable('Applications')->save($data->application);

                        ec('Claim Tran completed for App ID ' . $data->application->id);
                    } elseif ($arr['result']['status'] == 0) {
                        $data->transaction_status = 4;
                        $this->fetchTable('Claims')->save($data);
                        ec('Claim Tran failed for App ID ' . $data->application->id);
                    }
                }
            }
        } else {
            echo "Empty <hr>";
        }

        exit;
    }
}
