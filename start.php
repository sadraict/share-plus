<?php

elgg_register_event_handler('init', 'system', 'share_init');

function share_init() {
    elgg_extend_view('css/elgg', 'share/css');
    elgg_extend_view('js/elgg', 'share/js');

    // registered with priority < 500 so other plugins can remove share
    elgg_register_plugin_hook_handler('register', 'menu:river', 'share_river_menu_setup', 400);
    elgg_register_plugin_hook_handler('register', 'menu:entity', 'share_entity_menu_setup', 400);

    $actions_base = elgg_get_plugins_path() . 'share/actions/share';
    elgg_register_action('share/share', "$actions_base/share.php");
    elgg_register_action('share/delete', "$actions_base/delete.php");

	elgg_register_widget_type('share', elgg_echo('share:widget:name'), elgg_echo('share:widget:description'));

}


/**
 * Add a mark as share to entity menu at end of the menu
 */
function share_entity_menu_setup($hook, $type, $return, $params) {
    if (elgg_in_context('widgets')) {
        return $return;
    }

    $entity = $params['entity'];

  
    // share button
    $options = array(
        'name' => 'share',
	'text' => elgg_view('share/button', array('entity' => $entity)),
	'href' => false,
	'priority' => 1002,
    );
    $return[] = ElggMenuItem::factory($options);

    // share count
    $count = elgg_view('share/count', array('entity' => $entity));
    if ($count) {
        $options = array(
            'name' => 'share_count',
            'text' => $count,
            'href' => false,
            'priority' => 1003,
        );
	$return[] = ElggMenuItem::factory($options);
    }

    return $return;
}

/**
 * Add a mark as share button to river actions
 */
function share_river_menu_setup($hook, $type, $return, $params) {
    if (elgg_is_logged_in()) {	
        $item = $params['item'];

		// only share group creation
		if ($item->type == "group" && $item->view != "river/group/create") {
			return $return;
		}

		// don't share users
		if ($item->type == "user") {
			return $return;
		}      


  $object = $item->getObjectEntity();
        
          
        if (!elgg_in_context('widgets') && $item->annotation_id == 0) {
            if ($object->canAnnotate(0, 'share')) {
                $options = array(
                    'name' => 'share',
                    'href' => false,
                    'text' => elgg_view('share/button', array('entity' => $object)),
                    'is_action' => true,
                    'priority' => 102,
                );
                $return[] = ElggMenuItem::factory($options);
                
                // share count
                $count = elgg_view('share/count', array('entity' => $object));
                if ($count) {
                    $options = array(
                        'name' => 'share_count',
                        'text' => $count,
                        'href' => false,
                        'priority' => 103,
                    );
                    $return[] = ElggMenuItem::factory($options);
                }
            }
        }
    }

    return $return;
}

/**
 * Count how many people have marked an entity as share.
 *
 * @param  ElggEntity $entity
 *
 * @return int Number of markings
 */
function share_count($entity) {
    $type = $entity->getType();
    $params = array('entity' => $entity);
    $number = elgg_trigger_plugin_hook('share:count', $type, $params, false);

    if ($number) {
        return $number;
    }
    else {
        return $entity->countAnnotations('share');
    }
}

/**
 * Notify $user that $sharer marked his $entity as share.
 *
 * @param type $user
 * @param type $sharer
 * @param type $entity 
 */
function share_notify_user(ElggUser $user, ElggUser $sharer, ElggEntity $entity) {
	
    if (!$user instanceof ElggUser) {
        return false;
    }

    if (!$sharer instanceof ElggUser) {
        return false;
    }

    if (!$entity instanceof ElggEntity) {
        return false;
    }

    $title_str = $entity->title;
    if (!$title_str) {
        $title_str = elgg_get_excerpt($entity->description);
    }

    $site = get_config('site');

    $subject = elgg_echo('share:notifications:subject', array(
                         $sharer->name,
                         $title_str));

    $body = elgg_echo('share:notifications:body', array(
                      $user->name,
                      $sharer->name,
                      $title_str,
                      $site->name,
                      $entity->getURL(),
                      $sharer->getURL()));

    notify_user($user->guid,
                $sharer->guid,
                $subject,
                $body);
}