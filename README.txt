README.txt for the Simple LOCKSS Gateway (SLG)
==============================================

The Simple LOCKSS Gateway is a lightweight solution for integrating a LOCKSS box 
into your campus access infrastructure. It is intended specifically for providing access
to Open Access resources that are preserved in LOCKSS boxes, since it does not require
(or even support) authentication mechanisms, unlike Ezproxy or other dedicated remote-access
solutions.

The Simple LOCKSS Gateway rewrites URLs in the preserved content's HTML such that once a
user gains access to a resource via the Gateway, all links to other HTML pages on the
resource's web server (or in fact to all pages in the Gateway's host whitelist) are prepended 
with the Gateway URL. Only URLs pointing to hosts in the whitelist are rewritten in this way --
links to external sites are not modified -- and URLs that do not point to a whitelisted host
generate an error message. Therefore, the Simple LOCKSS Gateway cannot act as an open proxy 
to arbitrary URLs.


Installing and configuring the SLG
----------------------------------

Place the contents of slg.zip on a server running PHP 5.x. You will need to enable the cURL 
extension for the gateway to work. SLG comes with the PHP Simple HTML DOM Parser library 
(http://simplehtmldom.sourceforge.net/), distributed under the terms of the MIT License.

The IP address of the server you install SLG on must be registered with your LOCKSS box.
To do this, log into your box's admin interface, go to Content Access Control, add the
server's IP address to the Allow Access list, and then click on the Update button.

Once you have the SLG installed, you will need to define the following variables in the gateway.php
script:

1) The URL and proxy port number of your LOCKSS box. The port is the one indicated under Content Access Options/
  Content Server Options in your LOCKSS box admin interface.

  $lockss_box = 'lockssbox.yourlib.net:9091'; 

2) The list of hostnames that contain content preserved in your LOCKSS box. This list should contain the
  hostnames of all the servers that contain content you want accessed through the gateway.

  $allowed_hosts = array('journals.yourlib.net', 'journals.otherlib.net'); 

3) The URL of the SLG script on your server. Be sure to include the '?url=' at the end of the URL,
  as illustrated below.

  $this_script = 'http://yourwebsite.yourlib.net/pathto/gateway.php?url='; 


Routing users through the SLG
-----------------------------

To use the Simple LOCKSS Gateway, append the URL of the resource preserved in your LOCKSS box
to the end of the URL for the gateway script:

http://mylibrary.net/slg/gateway.php?url=http://some.publishers.journal.com

This new composite URL should be given to end users, or more conveniently, should be used on your
library's website instead of the direct URL to the resource. As long as users access a resource using
the SLG version of the URL, your LOCKSS box will return preserved content to them in the event that
the publisher's website goes down. When the publisher's website is working normally, users won't notice
anything other than the longer URLs.


