<?php

class User {

    // GENERAL

    public static function user_info($d) {

        // vars
        $user_id = isset($d['user_id']) && is_numeric($d['user_id']) ? $d['user_id'] : 0;
        $phone = isset($d['phone']) ? preg_replace('~\D+~', '', $d['phone']) : 0;
        // where
        if ($user_id) $where = "user_id='".$user_id."'";
        else if ($phone) $where = "phone='".$phone."'";
        else return [];
        // info
        $q = DB::query("SELECT user_id, first_name, last_name, phone, email, plot_id, access FROM users WHERE ".$where." LIMIT 1;") or die (DB::error());
        if ($row = DB::fetch_row($q)) {
            return [
                'id' => (int) $row['user_id'],
		'first_name' => $row['first_name'],
		'last_name'  => $row['last_name'],
		'phone'      => $row['phone'],
		'email'      => $row['email'],
		'plots'	     => $row['plot_id'],
		'access' => (int) $row['access']
            ];
        } else {
            return [
                'id' => 0,
		'first_name' => '',
		'last_name'  => '',
		'phone'      => '',
		'email'      => '',
		'plots'	     => '',
		'access' => 0
            ];
        }
    }


    public static function users_list($d = []) {
        // vars
        $search = isset($d['search']) && trim($d['search']) ? $d['search'] : '';
        $offset = isset($d['offset']) && is_numeric($d['offset']) ? $d['offset'] : 0;
        $limit = 20;
        $items = [];
        // where
        $where = [];                               
        if ($search) 
		$where[] = "first_name LIKE '%".$search."%' OR email LIKE '%".$search."%' OR phone LIKE '%".$search."%'";
        $where = $where ? "WHERE ".implode(" AND ", $where) : "";
        // info
        $q = DB::query("SELECT user_id, first_name, last_name, phone, email, last_login, plot_id 
			FROM users ".$where." ORDER BY user_id LIMIT ".$offset.", ".$limit.";") or die (DB::error()); 

        while ($row = DB::fetch_row($q)) {
            $items[] = [
                'id' => (int) $row['user_id'],
		'first_name' => $row['first_name'],
		'last_name'  => $row['last_name'],
		'phone'      => $row['phone'],
		'email'      => $row['email'],
		'plots'	     => ($row['plot_id'] ? explode(',', $row['plot_id']) : []),	
                'last_login' => date('Y/m/d', $row['last_login'])
            ];
        }
        // paginator
        $q = DB::query("SELECT count(*) FROM users ".$where.";");
        $count = ($row = DB::fetch_row($q)) ? $row['count(*)'] : 0;
        $url = 'users?';
        if ($search) $url .= '&search='.$search;
        paginator($count, $offset, $limit, $url, $paginator);

        return ['items' => $items, 'paginator' => $paginator];
    }

    public static function users_fetch($d = []) {
        $info = self::users_list($d);
        HTML::assign('users', $info['items']);
        return ['html' => HTML::fetch('./partials/users_table.html'), 'paginator' => $info['paginator']];
    }


    public static function users_list_plots($number) {
        // vars
        $items = [];
        // info
        $q = DB::query("SELECT user_id, plot_id, first_name, email, phone
            FROM users WHERE plot_id LIKE '%".$number."%' ORDER BY user_id;") or die (DB::error());
        while ($row = DB::fetch_row($q)) {
            $plot_ids = explode(',', $row['plot_id']);
            $val = false;
            foreach($plot_ids as $plot_id) if ($plot_id == $number) $val = true;
            if ($val) $items[] = [
                'id' => (int) $row['user_id'],
                'first_name' => $row['first_name'],
                'email' => $row['email'],
                'phone_str' => phone_formatting($row['phone'])
            ];
        }
        // output
        return $items;
    }


    // ACTIONS

    public static function user_edit_window($d = []) {
        $user_id = isset($d['user_id']) ? $d['user_id'] : 0;
        HTML::assign('user', self::user_info(['user_id' => $user_id]));
        return ['html' => HTML::fetch('./partials/user_edit.html')];
    }

    public static function user_edit_update($d = []) {
        // vars

        $user_id = isset($d['user_id']) && is_numeric($d['user_id']) ? $d['user_id'] : 0;
	$isValidate = true;
	$validatedFields = ['first_name', 'last_name', 'phone', 'email'];
	foreach($d as $key=>$val) {
		if (in_array($key, $validatedFields) && empty($val))
			$isValidate &= false;
	}
	if (!$isValidate) return;
	$first_name = $d['first_name'];
	$last_name  = $d['last_name'];
	$email = strtolower($d['email']);
	$phone = preg_replace('~\D+~', '', $d['phone']);
	$plots = explode(',', preg_replace('~[^0-9,]~', '', $d['plots']));
	$plots = array_diff($plots, array(''));

	if ($user_id) {
		$set = [];
		$set[] = "first_name = '$first_name'";
		$set[] = "last_name = '$last_name'";	
		
		$set[] = "email = '$email'";
		$set[] = "phone = '$phone'";
		$set[] = "plot_id = '" . implode(', ', $plots) . "'";
		$set = implode(',', $set);		
		DB::query("UPDATE users SET $set WHERE user_id = '$user_id'") or die(DB::error());

	} else {


		DB::query("INSERT INTO users(first_name, last_name, phone, email, plot_id) VALUES(
			  '$first_name', 
			  '$last_name', 
			  '$phone', 
			  '$email',
			  '" . implode(', ', $plots) . "')") or die(DB::error());
	}

        // output
        return self::users_fetch(['offset' => $d['offset']]);
    }
 
    public static function user_delete($d) {
        $user_id = isset($d['user_id']) ? $d['user_id'] : 0;
	DB::query("DELETE FROM users WHERE user_id = '$user_id'"); 

        // output
        return self::users_fetch(['offset' => $d['offset']]);

    }
	

}
