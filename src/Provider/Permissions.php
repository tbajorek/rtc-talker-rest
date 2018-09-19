<?php

namespace RtcTalker\Provider;

use RtcTalker\Model\User;

class Permissions {
    static private $permissions = null;
    static function getList() : array {
        if(self::$permissions === null) {
            $permissions = [];
            $permissions[USER::$GUEST] = [];
            $permissions[User::$USER] = [
                'user.change.availability',
                'user.logout',
                'user.view.my.profile',
                'user.update.my.profile',
                'user.view.my.company',
                'user.view.company.departments',
                'user.address.add',
                'user.address.update'
            ];
            $permissions[User::$MANAGER] = array_merge($permissions[User::$USER], [
                'company.create',
                'company.update',
                'company.delete',
                'manager.change.departments',
                'manager.department.create',
                'manager.department.update',
                'manager.department.remove',
                'manager.activate.user',
                'manager.invite.user',
                'manager.list.users',
            ]);
            $permissions[User::$ADMIN] = array_merge($permissions[User::$MANAGER], [
                'user.view.profile',
                'admin.view.company',
                'admin.view.company.departments',
                'admin.department.create',
                'admin.department.update',
                'admin.department.remove',
                'admin.activate.user',
                'admin.list.users',
                'admin.view.all.companies',
                'admin.activate.company'
            ]);
            self::$permissions = $permissions;
        }
        return self::$permissions;
    }
    static function getForRole(int $role) :array {
        $permissions = self::getList();
        if(key_exists($role, $permissions)) {
            return $permissions[$role];
        } else {
            return [];
        }
    }
}