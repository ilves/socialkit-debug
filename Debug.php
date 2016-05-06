<?php

namespace golive\socialkit\debug;

use yii\base\Application;
use yii\base\BootstrapInterface;
use yii\base\Component;
use yii\db\mysql\Schema;

class Debug extends Component
{
    public $debugTable = 'debug';
    public $autoCreate = false;
    public $whiteList;
    public $blackList;

    public function init()
    {
        parent::init();
        if ($this->autoCreate && \Yii::$app->db->schema->getTableSchema($this->debugTable) == null) {
            $this->createLogTable();
        }
        \Yii::$app->on(Application::EVENT_AFTER_REQUEST, [$this, 'logRequest']);
    }

    public function isLoggable()
    {
        $controllerId = \Yii::$app->controller->id;
        $actionId = \Yii::$app->controller->action->id;
        $key = $controllerId.'/'.$actionId;

        if (is_array($this->whiteList)) {
            return array_search($key, $this->whiteList) !== false;
        } else if (is_array($this->blackList)) {
            return array_search($key, $this->whiteList) === false;
        }

        return true;
    }
    
    public function logRequest()
    {
        if (!$this->isLoggable()) {
            return;
        }

        $db = \Yii::$app->db;
        $request = \Yii::$app->request;
        $user = \Yii::$app->user->getIdentity();

        $db->createCommand()->insert($this->debugTable, [
            'campaign' => \Yii::$app->campaign->id,
            'contact' => $user ? $user->getId() : null,
            'time' => date('Y-m-d H:i:s'),
            'url' => $request->getUrl(),
            'ip' => $request->getUserIP(),
            'user_agent' => $request->getUserAgent(),
            'get' => json_encode($request->get()),
            'post' => json_encode($request->post()),
            'session' => json_encode(isset($_SESSION) ? $_SESSION : []),
            'cookie' => json_encode(isset($_COOKIE) ? $_COOKIE : []),
        ])->execute();
    }

    protected function createLogTable()
    {
        \Yii::$app->db->createCommand()->createTable($this->debugTable, [
            'id' => Schema::TYPE_BIGPK,
            'campaign' => Schema::TYPE_BIGINT,
            'contact' => Schema::TYPE_BIGINT,
            'time' => Schema::TYPE_DATETIME,
            'url' => Schema::TYPE_STRING,
            'ip' => Schema::TYPE_STRING,
            'user_agent' => Schema::TYPE_TEXT,
            'get' => Schema::TYPE_TEXT,
            'post' => Schema::TYPE_TEXT,
            'session' => Schema::TYPE_TEXT,
            'cookie' => Schema::TYPE_TEXT,
        ])->execute();
    }
}