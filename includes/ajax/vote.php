<?php
    /**
     * Vote Ajax
     */
    require(ROOT_PATH . 'includes/cls_json.php');
    if (!isset($_REQUEST['vote']) || !isset($_REQUEST['options']) || !isset($_REQUEST['type']))
    {
        ecs_header("Location: ./\n");
        exit;
    }

    $res        = array('error' => 0, 'message' => '', 'content' => '');

    $vote_id    = intval($_POST['vote']);
    $options    = trim($_POST['options']);
    $type       = intval($_POST['type']);
    $ip_address = real_ip();

    if (vote_already_submited($vote_id, $ip_address))
    {
        $res['error']   = 1;
        $res['message'] = $_LANG['vote_ip_same'];
    }
    else
    {
        save_vote($vote_id, $ip_address, $options);

        $vote = get_vote($vote_id);
        if (!empty($vote))
        {
            $smarty->assign('vote_id', $vote['id']);
            $smarty->assign('vote',    $vote['content']);
        }

        $str = $smarty->fetch("library/vote.lbi");

        $pattern = '/(?:<(\w+)[^>]*> .*?)?<div\s+id="ECS_VOTE">(.*)<\/div>(?:.*?<\/\1>)?/is';

        if (preg_match($pattern, $str, $match))
        {
            $res['content'] = $match[2];
        }
        $res['message'] = $_LANG['vote_success'];
    }

    $json = new JSON;

    die($json->encode($res));

 ?>