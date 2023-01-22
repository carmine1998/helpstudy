<?php
class Database
{
    const host = 'localhost';
    const database = 'help_study';
    const username = 'root';
    const password = '';

    public static function getConnection(){
        $con = mysqli_connect(self::host, self::username, self::password, self::database);

        return $con;
    }

}