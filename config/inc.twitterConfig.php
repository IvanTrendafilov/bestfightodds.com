<?php

define('TWITTER_ENABLED', false);
define('TWITTER_DEV_MODE', true); //In dev mode, no actual tweets are created. Instead they are just echo'ed to the prompt

define('TWITTER_CONSUMER_KEY', 'OKspPO3VjSMtgZTXR6VXUg');
define('TWITTER_CONSUMER_SECRET', 'yheM1NCNx4BOdZyh3aeh1UPIQHfn4yRZBL7r3BjiU');
define('TWITTER_OAUTH_TOKEN', '47427385-7rgoivFKNU7Bv1ABDgqeY3H7ij9nx2i47TPdlD1U2');
define('TWITTER_OATUH_TOKEN_SECRET', 'S3N7HNMXHAXdFQoIhKrleT1rr3yOoRLzsH8vzmSzg');

define('TWITTER_GROUP_MATCHUPS', true); //Used to indicate if we group multiple matchups on the same event into one tweet (does not include main events)
define('TWITTER_TEMPLATE_SINGLE', '<E>: <T1> (<T1O>) vs. <T2> (<T2O>) https://bestfightodds.com'); //Template used to tweet one matchup in one tweet. <E> = Event name, <T1> = Team one, <T2> = team two, <T1O> = team one odds, <T2O> = team two odds
define('TWITTER_TEMPLATE_MULTI', 'New lines for <E> posted https://bestfightodds.com'); //Template used to tweet multiple matchups in one tweet (only available if TWITTER_GROUP_MATCHUPS is enabled)

?>
