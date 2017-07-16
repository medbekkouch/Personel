<?php

/**
 * Here we override the default HTML output of drupal.
 * refer to https://drupal.org/node/457740
 */
/**
 * @file hanldles all Lonely Planet template file
 */
// Auto-rebuild the theme registry during theme development.
if (theme_get_setting('clear_registry')) {
  // Rebuild .info data.
  system_rebuild_theme_data();
  // Rebuild theme registry.
  drupal_theme_rebuild();
}

// Add Zen Tabs styles
if (theme_get_setting('lonelyplanet_tabs')) {
  drupal_add_css(drupal_get_path('theme', 'lonelyplanet') . '/css/tabs.css');
}

function lonelyplanet_preprocess_html(&$vars) {
  global $user, $language;
  
  // Add role name classes (to allow css based show for admin/hidden from user)
  foreach ($user->roles as $role) {
    $vars['classes_array'][] = 'role-' . lonelyplanet_id_safe($role);
  }
  
  // HTML Attributes
  // Use a proper attributes array for the html attributes.
  $vars['html_attributes'] = array();
  $vars['html_attributes']['lang'][] = $language->language;
  $vars['html_attributes']['dir'][] = $language->dir;
  
  // Convert RDF Namespaces into structured data using drupal_attributes.
  $vars['rdf_namespaces'] = array();
  if (function_exists('rdf_get_namespaces')) {
    foreach (rdf_get_namespaces() as $prefix => $uri) {
      $prefixes[] = $prefix . ': ' . $uri;
    }
    $vars['rdf_namespaces']['prefix'] = implode(' ', $prefixes);
  }
  
  // Flatten the HTML attributes and RDF namespaces arrays.
  $vars['html_attributes'] = drupal_attributes($vars['html_attributes']);
  $vars['rdf_namespaces'] = drupal_attributes($vars['rdf_namespaces']);
  
  if (!$vars['is_front']) {
    // Add unique classes for each page and website section
    $path = drupal_get_path_alias($_GET['q']);
    list($section,) = explode('/', $path, 2);
    $vars['classes_array'][] = 'with-subnav';
    $vars['classes_array'][] = lonelyplanet_id_safe('page-' . $path);
    $vars['classes_array'][] = lonelyplanet_id_safe('section-' . $section);
    
    if (arg(0) == 'node') {
      if (arg(1) == 'add') {
        if ($section == 'node') {
          // Remove 'section-node'
          array_pop($vars['classes_array']);
        }
        // Add 'section-node-add'
        $vars['classes_array'][] = 'section-node-add';
      }
      elseif (is_numeric(arg(1)) && (arg(2) == 'edit' || arg(2) == 'delete')) {
        if ($section == 'node') {
          // Remove 'section-node'
          array_pop($vars['classes_array']);
        }
        // Add 'section-node-edit' or 'section-node-delete'
        $vars['classes_array'][] = 'section-node-' . arg(2);
      }
    }
  }
  //for normal un-themed edit pages
  if ((arg(0) == 'node') && (arg(2) == 'edit')) {
    $vars['template_files'][] = 'page';
  }
  
  // Add IE classes.
  if (theme_get_setting('lonelyplanet_ie_enabled')) {
    $lonelyplanet_ie_enabled_versions = theme_get_setting('lonelyplanet_ie_enabled_versions');
    if (in_array('ie8', $lonelyplanet_ie_enabled_versions, TRUE)) {
      drupal_add_css(path_to_theme() . '/css/ie8.css', array(
        'group' => CSS_THEME,
        'browsers' => array('IE' => 'IE 8', '!IE' => FALSE),
        'preprocess' => FALSE,
      ));
      drupal_add_js(path_to_theme() . '/js/build/selectivizr-min.js');
    }
    if (in_array('ie9', $lonelyplanet_ie_enabled_versions, TRUE)) {
      drupal_add_css(path_to_theme() . '/css/ie9.css', array(
        'group' => CSS_THEME,
        'browsers' => array('IE' => 'IE 9', '!IE' => FALSE),
        'preprocess' => FALSE,
      ));
    }
    if (in_array('ie10', $lonelyplanet_ie_enabled_versions, TRUE)) {
      drupal_add_css(path_to_theme() . '/css/ie10.css', array(
        'group' => CSS_THEME,
        'browsers' => array('IE' => 'IE 10', '!IE' => FALSE),
        'preprocess' => FALSE,
      ));
    }
  }
  
}

function lonelyplanet_preprocess_page(&$vars, $hook) {
  if (isset($vars['node_title'])) {
    $vars['title'] = $vars['node_title'];
  }
  // Adding classes whether #navigation is here or not
  if (!empty($vars['main_menu']) or !empty($vars['sub_menu'])) {
    $vars['classes_array'][] = 'with-navigation';
  }
  if (!empty($vars['secondary_menu'])) {
    $vars['classes_array'][] = 'with-subnav';
  }
  
  // Add first/last classes to node listings about to be rendered.
  if (isset($vars['page']['content']['system_main']['nodes'])) {
    // All nids about to be loaded (without the #sorted attribute).
    $nids = element_children($vars['page']['content']['system_main']['nodes']);
    // Only add first/last classes if there is more than 1 node being rendered.
    if (count($nids) > 1) {
      $first_nid = reset($nids);
      $last_nid = end($nids);
      $first_node = $vars['page']['content']['system_main']['nodes'][$first_nid]['#node'];
      $first_node->classes_array = array('first');
      $last_node = $vars['page']['content']['system_main']['nodes'][$last_nid]['#node'];
      $last_node->classes_array = array('last');
    }
  }
  
  // Allow page override template suggestions based on node content type.
  if (isset($vars['node']->type) && isset($vars['node']->nid)) {
    $vars['theme_hook_suggestions'][] = 'page__' . $vars['node']->type;
    $vars['theme_hook_suggestions'][] = "page__node__" . $vars['node']->nid;
  }
  if (isset($_SESSION['messages']['popup'])) {
    $add_to_cart_messages = $_SESSION['messages']['popup'];
    $markup = '';
    if (!empty($add_to_cart_messages)) {
      foreach ($add_to_cart_messages as $add_to_cart_message) {
        $markup .= $add_to_cart_message;
      }
    }
    $vars['commerce_add_to_cart_popups'] = array(
      '#type' => 'container',
      '#attributes' => array('class' => array('popup-container-cart'),'id' => array('cart-popin')),
      'content' => array(
        '#type' => 'markup',
        '#markup' => $markup)
    );
    unset($_SESSION['messages']['popup']);
  }
  else {
    $vars['commerce_add_to_cart_popups'] = NULL;
  }
}

function lonelyplanet_preprocess_node(&$vars) {
  // Add a striping class.
  $vars['classes_array'][] = 'node-' . $vars['zebra'];
  
  // Add $unpublished variable.
  $vars['unpublished'] = (!$vars['status']) ? TRUE : FALSE;
  
  // Merge first/last class (from lonelyplanet_preprocess_page) into classes array of current node object.
  $node = $vars['node'];
  if (!empty($node->classes_array)) {
    $vars['classes_array'] = array_merge($vars['classes_array'], $node->classes_array);
  }
}

function lonelyplanet_preprocess_block(&$vars, $hook) {
  // Add a striping class.
  $vars['classes_array'][] = 'block-' . $vars['block_zebra'];
  
  // Add first/last block classes
  $first_last = "";
  // If block id (count) is 1, it's first in region.
  if ($vars['block_id'] == '1') {
    $first_last = "first";
    $vars['classes_array'][] = $first_last;
  }
  // Count amount of blocks about to be rendered in that region.
  $block_count = count(block_list($vars['elements']['#block']->region));
  if ($vars['block_id'] == $block_count) {
    $first_last = "last";
    $vars['classes_array'][] = $first_last;
  }
  
  // Simple Classes.
  $vars['classes_array'] = array('block');
}

/**
 * Return a themed breadcrumb trail.
 *
 * @param $breadcrumb
 *   An array containing the breadcrumb links.
 * @return
 *   A string containing the breadcrumb output.
 */

function lonelyplanet_get_breadcrumb($breadcrumb, $destination = NULL, $type = NULL) {
  $output = '<ol>';
  if (count($breadcrumb) > 0) {
    $last_element = $lastElement = end($breadcrumb);
    foreach ($breadcrumb as $item) {
      $name = strip_tags($item);
      $output .= '<li>';
      if ($item != $last_element) {
        $a = new SimpleXMLElement($item);
        if (strpos($a['href'], 'taxonomy/term') == TRUE) {
          $url = explode('term/', $a['href']);
          $tid = $url[1];
          $term = taxonomy_term_load($tid);
          if ($term->vocabulary_machine_name == 'destination') {
            $nid = lp_helpers_get_nid_by_tid($tid);
            $url = url('node/' . $nid);
          }
          elseif ($term->vocabulary_machine_name == 'magazine') {
            $url = $term->vocabulary_machine_name . '/' . $term->name;
          }
          elseif ($term->vocabulary_machine_name == 'forums') {
            $url = url('forum/' . $term->tid);
          }
          $output .= '<a href="';
          $output .= $url;
          $output .= '">';
        }
        else {
          $output .= '<a href="';
          $output .= $a['href'];
          $output .= '">';
        }
        $output .= $name;
        $output .= '</a>';
      }
      else {
        if ($type != NULL and $destination != NULL and $type == 'hotel') {
          $hebergement_nid = lp_helpers_get_article_by_rubrique($destination, variable_get('hebergement_tid'));
          $hebergement = node_load($hebergement_nid[0]);
          $url = url('node/' . $hebergement_nid[0]);
          $output .= '<a href="';
          $output .= $url;
          $output .= '">';
          $output .= $hebergement->title;
          $output .= '</a>';
        }
        $output .= '<span> ';
        $output .= $name;
      }
      $output .= '</li>';
    }
  }
  else {
    $output .= '</li><a href="/">Accueil</a>';
  }
  $output .= '</ol>';
  
  return $output;
}


/**
 * Converts a string to a suitable html ID attribute.
 *
 * http://www.w3.org/TR/html4/struct/global.html#h-7.5.2 specifies what makes a
 * valid ID attribute in HTML. This function:
 *
 * - Ensure an ID starts with an alpha character by optionally adding an 'n'.
 * - Replaces any character except A-Z, numbers, and underscores with dashes.
 * - Converts entire string to lowercase.
 *
 * @param $string
 *  The string
 * @return
 *  The converted string
 */
function lonelyplanet_id_safe($string) {
  // Replace with dashes anything that isn't A-Z, numbers, dashes, or underscores.
  $string = strtolower(preg_replace('/[^a-zA-Z0-9_-]+/', '-', $string));
  // If the first character is not a-z, add 'n' in front.
  if (!ctype_lower($string{0})) { // Don't use ctype_alpha since its locale aware.
    $string = 'id' . $string;
  }
  
  return $string;
}

/**
 * Generate the HTML output for a menu link and submenu.
 *
 * @param $variables
 *  An associative array containing:
 *   - element: Structured array data for a menu link.
 *
 * @return
 *  A themed HTML string.
 *
 * @ingroup themeable
 *
 */
function lonelyplanet_menu_link(array $variables) {
  $element = $variables['element'];
  $sub_menu = '';
  
  if ($element['#below']) {
    $sub_menu = drupal_render($element['#below']);
  }
  $output = l($element['#title'], $element['#href'], $element['#localized_options']);
  // Adding a class depending on the TITLE of the link (not constant)
  $element['#attributes']['class'][] = lonelyplanet_id_safe($element['#title']);
  // Adding a class depending on the ID of the link (constant)
  if (isset($element['#original_link']['mlid']) && !empty($element['#original_link']['mlid'])) {
    $element['#attributes']['class'][] = 'mid-' . $element['#original_link']['mlid'];
  }
  
  return '<li' . drupal_attributes($element['#attributes']) . '>' . $output . $sub_menu . "</li>\n";
}

/**
 * Override or insert variables into theme_menu_local_task().
 */
function lonelyplanet_preprocess_menu_local_task(&$variables) {
  $link =& $variables['element']['#link'];
  
  // If the link does not contain HTML already, check_plain() it now.
  // After we set 'html'=TRUE the link will not be sanitized by l().
  if (empty($link['localized_options']['html'])) {
    $link['title'] = check_plain($link['title']);
  }
  $link['localized_options']['html'] = TRUE;
  $link['title'] = '<span class="tab ' . drupal_html_class('task-' . $link['title']) . '">' . $link['title'] . '</span>';
}

/**
 * Duplicate of theme_menu_local_tasks() but adds clearfix to tabs.
 */
function lonelyplanet_menu_local_tasks(&$variables) {
  $output = '';
  
  if (!empty($variables['primary'])) {
    $variables['primary']['#prefix'] = '<h2 class="element-invisible">' . t('Primary tabs') . '</h2>';
    $variables['primary']['#prefix'] .= '<ul class="tabs primary clearfix">';
    $variables['primary']['#suffix'] = '</ul>';
    $output .= drupal_render($variables['primary']);
  }
  if (!empty($variables['secondary'])) {
    $variables['secondary']['#prefix'] = '<h2 class="element-invisible">' . t('Secondary tabs') . '</h2>';
    $variables['secondary']['#prefix'] .= '<ul class="tabs secondary clearfix">';
    $variables['secondary']['#suffix'] = '</ul>';
    $output .= drupal_render($variables['secondary']);
  }
  
  return $output;
}

/**
 * trims text to a space then adds ellipses if desired
 * @param string $input text to trim
 * @param int $length in characters to trim to
 * @param bool $ellipses if ellipses (...) are to be added
 * @return string
 */
function lp_trim_text($input, $length, $ellipses = TRUE) {
  $input = strip_tags($input);
  //no need to trim, already shorter than trim length
  if (strlen($input) <= $length) {
    return $input;
  }
  //find last space within length
  $last_space = strrpos(substr($input, 0, $length + 1), ' ');
  $trimmed_text = substr($input, 0, $last_space);
  //add ellipses (...)
  if ($ellipses) {
    $trimmed_text .= '...';
  }
  
  return $trimmed_text;
}

/**
 * Forge SEO friendly image (alt, title, copyright, etc...)
 * @param $fid
 * @param null $style
 * @param array $classes
 * @return string
 */
function lp_render_seo_friendly_image($fid, $style = NULL, $classes = array()) {
  if (!$fid) {
    return '';
  }
  $file = file_load($fid);
  if (isset($file->field_media_copyright[LANGUAGE_NONE][0]['value'])) {
    $classes[] = 'img-copyrights';
  }
  $img_tag = '<img class="' . join(' ', $classes) . '" ';
  if ($style) {
    $img_tag .= 'src="' . image_style_url($style, $file->uri) . '" alt="';
  }
  else {
    $img_tag .= 'src="' . file_create_url($file->uri) . '" alt="';
  }
  $img_tag .= isset($file->field_file_image_alt_text[LANGUAGE_NONE][0]['value']) ? $file->field_file_image_alt_text[LANGUAGE_NONE][0]['value'] . '" title="' : '" title="';
  $img_tag .= isset($file->field_file_image_title_text[LANGUAGE_NONE][0]['value']) ? $file->field_file_image_title_text[LANGUAGE_NONE][0]['value'] . '" data-copyright="' : '" data-copyright="';
  $img_tag .= isset($file->field_media_copyright[LANGUAGE_NONE][0]['value']) ? $file->field_media_copyright[LANGUAGE_NONE][0]['value'] . '" data-copyright-url="' : '" data-copyright-url="';
  $img_tag .= isset($file->field_media_copyright_url[LANGUAGE_NONE][0]['value']) ? $file->field_media_copyright_url[LANGUAGE_NONE][0]['value'] . '"' : '"';
  $img_tag .= '/>';
  
  return $img_tag;
}

function lonely_planet_preprocess_page(&$vars) {
  $search_form = drupal_get_form('search_block_form');
  $vars['search'] = drupal_render($search_form);
}


/**
 * overrides three user functions
 */
function lonelyplanet_theme() {
  $items = array();
  
  $items['user_login'] = array(
    'render element' => 'form',
    'path' => drupal_get_path('theme', 'lonelyplanet') . '/templates/users',
    'template' => 'user-login',
  );
  $items['user_register_form'] = array(
    'render element' => 'form',
    'path' => drupal_get_path('theme', 'lonelyplanet') . '/templates/users',
    'template' => 'user-register-form',
  );
  $items['user_pass'] = array(
    'render element' => 'form',
    'path' => drupal_get_path('theme', 'lonelyplanet') . '/templates/users',
    'template' => 'user-pass',
  );
  
  return $items;
}

/*
*  lonelyplanet_link(&$variables)
*
*  Cette fonction est un hook qui est utilisé pour modifier les liens des forums
*  En attendant une meilleure solution qui serait de faire un override de /comment/reply
*
*/
function lonelyplanet_link($variables) {
  //lp_log($variables['path']);
  if (substr($variables['path'],0,14)=='comment/reply/') {
    $variables['path'] = str_replace('comment/reply/','forum/post-reply/',$variables['path']);
    return theme_link($variables);
  } elseif (preg_match('/(node|comment)\/[0-9]+\/delete/',$variables['path'])) {
    //$variables['path'] = "overlay/" . $variables['path'];
    //      $variables['options']['attributes']['onclick'] = "event.preventDefault();overlay_dialog('".base_path()."{$variables['path']}');return false;";
    return theme_link($variables);
  } elseif (substr($variables['path'],0,15)=='node/add/forum/') {
    $output = <<<EOT
      <div class="action_button green" >
          <a href="{$variables['path']}">{$variables['text']}</a><span class="arrow"></span>
            </div>
EOT;
    return $output;
  } else if (substr($variables['path'],0,26)=='flag_inappropriate_content') {
    
    return lonelyplanet_notify_admin_button($variables['path']);
    
  } else {
    return theme_link($variables);
  }
}
function lonelyplanet_notify_admin_button($path) {
  global $user;
  global $base_url;
  $path = $base_url.'/'.$path;
  
  $message = t("Signaler ce contenu à l'administrateur comme inapproprié");
  $output = <<<EOT
  <div class="notify_admin"><a href="#" class="notify-inappropriate-content" data-uid="{$user->uid}"  data-path="$path" title="$message" ><span class='icons-report'></span>signaler</a><div class='notification_acknowledge'></div></div>
EOT;
  return $output;
}
