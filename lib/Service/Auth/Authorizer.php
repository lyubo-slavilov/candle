<?php
namespace Service\Auth;

use Model\Entity\User;

use Flame\Flame;
use Flame\Exception\EmptyResultException;
use Flame\Exception\NotASingleResultException;

use Candle\Session\Session;

class Authorizer {

    private static $user;

    private function checkSession()
    {
        $session = Session::getInstance();

        return $session->get('user', false);
    }


    private function loadUser()
    {
        $session = Session::getInstance();

        $user = $session->get('user', false);

        if (!$user) {
            throw new \Exception('Invalid session');
        }

        $userEnt = Flame::getRepo('User')->find($user['id']);

        self::$user = $userEnt;

    }


    public function initFromSsoData($data)
    {
        try {
            $userEnt = Flame::getRepo('User')->findSingleBy(array(
                'username' => $data->userName
            ));

        } catch (EmptyResultException $ex) {
            $userEnt = new User();
            $userEnt->setUsername($data->userName);
            $userEnt->setSalt('notused');
            $userEnt->setPassword('notused');
            $userEnt->save();
        }

        self::$user = $userEnt;
        $session = Session::getInstance();
        $session->set('user', $userEnt);
    }

    public function getUser()
    {
        $user = $this->checkSession();
        return $user;
    }

    public function authenticate($username, $password)
    {
        try {
            $user = Flame::getRepo('User')->findSingleBy(array('username' => $username));
        } catch (EmptyResultException $ex) {
            throw new AuthorizationException('Invalid username or password');
        }

        $sha1pass = sha1($user->getSalt() . $password);
        if ($sha1pass != $user->getPassword()) {
            throw new AuthorizationException('Invalid username or password');
        }


        self::$user = $user;
        $session = Session::getInstance();

        $session->set('user', array(
            'id' => $user->getId(),
            'username' => $user->getUsername(),
        ));

        return true;
    }

    public function pretendTo($username)
    {
        $session =Session::getInstance();
        $newUser = Flame::getRepo('User')->findOneBy(array(
            'username' => $username
        ));
        $oldUser = $this->getUser();
        if ($newUser) {
            $session->set('pretender', $oldUser);
            $session->set('user', $newUser);
        }
    }

    public function reveal()
    {
        $session = Session::getInstance();
        $oldUser = $session->get('pretender');

        if (!$oldUser) {
            throw new AuthorizationException('Can not reveal somebody who is not pretending');
        }
        $session->set('user', $oldUser);
        $session->clear('pretender');
    }
}