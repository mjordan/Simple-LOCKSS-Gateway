<?php

/*********************************************************************************************
* Simple LOCKSS Gateway, a script that acts as a proxy between end users and a LOCKSS box.
* Last modified 2011-01-12 mjordan@sfu.ca.
* 
* Requires PHP's cURL extension and the PHP Simple HTML DOM Parser library 
* (http://simplehtmldom.sourceforge.net/). Licensed under The MIT License; see MIT-LICENSE.txt 
* for details.
**********************************************************************************************/

// Change $lockss_box, $allowed_hosts, and $this_script as described in README.txt.
$lockss_box = 'cpln.lib.sfu.ca:9091';
// Whitelist of hosts to allow in rewritten URLs. 
$allowed_hosts = array('pkp.sfu.ca', 'lib-drupal2.lib.sfu.ca');
// The URL of this script, which is prepended to proxied URLs below.
$this_script = 'http://lib-general.lib.sfu.ca/slg/gateway.php?url=';
$log_var_file = '/tmp/slg_log.txt';

// You should not have to change anything below this line.

// List of element => attribute pairs to rewrite. <script> tags are handled by a special case.
$rewrite_elements = array(
  'a' => 'href',
  'img' => 'src',
  'link' => 'href',
  'object' => 'data',
);

// Grab the destination URL, e.g. http://pkp.sfu.ca/ojs/demo/present/index.php/demojournal.
$url = $_GET['url'];
if (empty($url)) {
  echo '<html><body>Error: URL parameter is required</body></html>';
  exit;
}

// Define a whitelist of hosts that are allowed to be proxied through this script.
$url_parts = parse_url($url);
if (!in_array($url_parts['host'], $allowed_hosts)) {
  echo '<html><body>Error: Unregistered URL</body></html>';
  exit;
}

// Include the PHP Simple HTML DOM Parser library.
require_once('simplehtmldom/simple_html_dom.php');

// Create and execute the cURL handle on the destination URL.
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_PROXY, $lockss_box);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
$destination_content = curl_exec($ch);

// Get the HTTP response from the LOCKSS box.
$info = curl_getinfo($ch);
// Grab the HTTP content type header to pass back to the client.
$content_type = $info['content_type'];

// LOCKSS returns a custom 404 page that lists likely AU URLs, so we deal with it first.
if ($info['http_code'] == '404') {
  $proxied_html = str_get_html($destination_content);
  foreach($proxied_html->find('a') as $a) {
    $href_host = parse_url($a->href, PHP_URL_HOST);
    if (in_array($href_host, $allowed_hosts)) {
      $a->href = $this_script . $a->href;
    }
  }
  // Return the custom 404 page with the rewritten URLs to the client. 
  echo $proxied_html;
} else {
  // We don't want to 'rewrite' non-HTML content like images or CSS. If the content retrieved from
  // the LOCKSS proxy is not HTML, send it back to the client as is, along with the corresponding
  // content-type header.
  if (!preg_match('/^text\/html/', $content_type)) {
    header('Content-type: ' . $content_type);
    echo $destination_content;
    exit;
  }
  else {
    // If the response code was not 404, assume we want to rewrite the HTML and return it to the client.
    // Create the DOM object that we will be manipulating. 
    $proxied_html = str_get_html($destination_content);
    // Find all the element => attribute combinations we want to rewrite and prepend the $this_script 
    // URL if the URL's host name is in $allowed_hosts.
    foreach ($rewrite_elements as $element => $attribute) {
      foreach($proxied_html->find($element) as $e) {
        $host = parse_url($e->$attribute, PHP_URL_HOST);
        if (in_array($host, $allowed_hosts)) {
          $e->$attribute = $this_script . $e->$attribute;
        }
      }
    }

    // PHP Simple HTML DOM Parser library doesn't find <a hrefs> or any other attriubutes in JavaScript, 
    // so we need to implement a special case where we rewrite these URLs manually. 
    foreach($proxied_html->find('script') as $script) {
      $host_pattern = '/(' . $url_parts['scheme'] . '\:\/\/' . $url_parts['host'] . ')/';
      $replacement_host_pattern = "$this_script$1";
      $script->outertext = preg_replace($host_pattern, $replacement_host_pattern, $script->outertext);
    }

    // Send the rewritten HTML to the client.
    header('Content-type: ' . $content_type);
    echo $proxied_html;
  }
}

/**
 * Log any variable to a file. Adapted from the Drupal devel module's dd() function.
 * Set $utf8 to FALSE if you want incoming text to not be converted.
 */
function log_var($data, $label = NULL) {
  global $log_var_file;
  ob_start();
  print_r($data);
  $string = ob_get_clean();
  if ($label) {
    $out = $label .': '. $string;
  }
  else {
    $out = $string;
  }
  $out .= "\n";

  if (file_put_contents('/tmp/lockss.log', $out, FILE_APPEND) === FALSE) {
    return FALSE;
  }
}


?>
