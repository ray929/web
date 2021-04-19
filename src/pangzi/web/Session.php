<?php

namespace pangzi\web;

class Session implements \SessionHandlerInterface
{
    private $session_sqlite_db = null;
    private $session_sqlite_table;

    public function open($savePath, $sessionName)
    {
        if (!is_null($this->session_sqlite_db)) {
            trigger_error('Bad call to open(): connection already opened.', E_USER_NOTICE);
        }

        if (false === realpath($savePath)) {
            mkdir($savePath, 0700, true);
        }

        if (empty($savePath)) {
            // if (php_sapi_name() != 'cli')
            //     error_log("Session save path is empty! (use /tmp)", E_USER_ERROR);

            $savePath = RUNTIME_PATH;
        }

        if (!is_dir($savePath) || !is_writable($savePath)) {
            trigger_error("Invalid session save path - $savePath", E_USER_ERROR);
        }

        $dbOptions = array(
            \PDO::ATTR_TIMEOUT => 2, // 2 sec timeout
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_WARNING // \PDO::ERRMODE_EXCEPTION,
            //             \PDO::ATTR_AUTOCOMMIT => false,
        );

        $dsn = 'sqlite:' . $savePath . DIRECTORY_SEPARATOR . 'sessions.sqlite';
        $pdo = $this->session_sqlite_db = new \PDO($dsn, NULL, NULL, $dbOptions);
        $table = $this->session_sqlite_table = '"' . strtolower($sessionName) . '"';

        $pdo->exec("PRAGMA page_size=4096"); // default 1k
        $pdo->exec("PRAGMA journal_mode=WAL"); // enable WAL-mode (sqlite 3.7+ нужен)
        $pdo->exec('PRAGMA journal_size_limit = ' . (4 * 1024 * 1024)); // size of WAL-journal = 4Mb
        $pdo->exec('PRAGMA synchronous = 1'); // 2-FULL, 1-NORMAL, 0-OFF 
        $pdo->exec('PRAGMA temp_store=MEMORY');
        $pdo->exec('PRAGMA cache_size = 4000'); // double cache size in RAM
        $pdo->exec('PRAGMA encoding="UTF-8"');
        $pdo->exec('PRAGMA auto_vacuum=FULL');
        $pdo->exec('PRAGMA synchronous=NORMAL');
        // 	$pdo->exec('PRAGMA secure_delete=1');
        // 	$pdo->exec('PRAGMA writable_schema=0');

        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS {$table} (
			id TEXT PRIMARY KEY NOT NULL,
			data TEXT CHECK (TYPEOF(data) = 'text') NOT NULL DEFAULT '',
			time INTEGER CHECK (TYPEOF(time) = 'integer') NOT NULL
		)"
        ); // time DEFAULT (strftime('%s', 'now'))

        return true;
    }

    public function close()
    {
        $this->session_sqlite_db = null;
        return true;
    }

    public function read($id)
    {
        $file = session_save_path() . "/sess_$id";
        if (file_exists($file)) {
            $row = $this->session_sqlite_db->query("SELECT id FROM $this->session_sqlite_table WHERE id = " . $this->session_sqlite_db->quote($id))->fetch();
            if (empty($row))
                $this->write($id, file_get_contents($file), filemtime($file));
        }

        $sql = "SELECT data FROM {$this->session_sqlite_table} WHERE id = :id LIMIT 1";
        $sth = $this->session_sqlite_db->prepare($sql);
        $sth->bindParam(':id', $id, \PDO::PARAM_STR);
        $sth->execute();
        $rows = $sth->fetchAll(\PDO::FETCH_NUM);
        return $rows ? $rows[0][0] : '';
    }

    public function write($id, $data)
    {
        $sql = "REPLACE INTO {$this->session_sqlite_table} (id, data, time) VALUES (:id, :data, :time)";
        $sth = $this->session_sqlite_db->prepare($sql);
        $sth->bindParam(':id', $id, \PDO::PARAM_STR);
        $sth->bindValue(':data', $data, \PDO::PARAM_STR);
        $sth->bindValue(':time', time(), \PDO::PARAM_INT);
        return $sth->execute();
    }

    public function destroy($id)
    {
        $sql = "DELETE FROM {$this->session_sqlite_table} WHERE id = :id";
        $sth = $this->session_sqlite_db->prepare($sql);
        $sth->bindParam(':id', $id, \PDO::PARAM_STR);
        return $sth->execute();
    }

    public function gc($maxlifetime)
    {
        
        $sql = "DELETE FROM {$this->session_sqlite_table} WHERE time < :time";
        $sth = $this->session_sqlite_db->prepare($sql);
        $sth->bindValue(':time', time() - $maxlifetime, \PDO::PARAM_INT);
        $result = $sth->execute();

        error_log('session_sqlite_gc = ' . $sth->rowCount());
        return $result;
    }


    static public function Load() {
        if(is_readable(session_save_path().DIRECTORY_SEPARATOR.'sessions.sqlite')) {
            // connect our session handler
            session_set_save_handler(
                'session_sqlite_open',
                'session_sqlite_close',
                'session_sqlite_read',
                'session_sqlite_write',
                'session_sqlite_destroy',
                'session_sqlite_gc'
            );
        
            register_shutdown_function('session_write_close');
        }
        else 
            error_log(session_save_path().DIRECTORY_SEPARATOR.'sessions.sqlite - not readable! (switch to default session handler)');
    }
}
