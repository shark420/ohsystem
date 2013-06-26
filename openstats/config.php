<?php  
if (strstr($_SERVER['REQUEST_URI'], basename(__FILE__) ) ) {header('HTTP/1.1 404 Not Found'); die; }
$OSAppID = 'openstats_1';

$SN = session_name( $OSAppID );
if (!isset($_SESSION)) { session_start(); }

$server = 'localhost';
$username = 'root';
$password = '';
$database = 'openstats';

$website = 'http://localhost/openstats/';
$HomeTitle = 'DotA OpenStats 4';
$HomeDesc = 'DotA OpenStats 4';
$HomeKeywords = 'dota, games, heroes, players, best players, top list, top players, statistics';

$default_language = 'english';
$DateFormat = 'd.m.Y, H:i';
$DefaultStyle = 'blogger';

$LogoText = 'DotA OpenStats 4';

//FACEBOOK APP SETUP
//Enable Login via Facebook
$FBLogin = '0';
//FB Application ID
$FacebookAppID = 'FB Application ID';
//FB Application Secret
$FacebookAppSecret = 'FB Application Secret';

//Forum and WP integration
$PHPbb3Integration = '0';
$SMFIntegration = '0';
$WPIntegration = '0';

//Path to phpbb3 forum
$phpbb_forum = 'forum/';
$phpbb_forum_url = 'http://localhost/openstats/forum/';
//Path to SMF forum
$smf_forum = 'smf/';
$smf_forum_url = 'http://localhost/openstats/smf/';

//Path to wordpress
$wp_path = 'wordpress/';
$wp_url = 'http://localhost/openstats/wordpress/';

//$HeroVote = '1'; //not working - removed
$HeroVote = ""; //disabled
$HeroVoteShow = '20';

$HeroFileExt = 'gif';

$ReplayLocation = 'replays';

$GamesPerPage = '20';
$TopPlayersPerPage = '30';
//Heroes AND Items per page
$HeroesPerPage = '20';
$ItemsPerPage = '20';

$NewsPerPage = '5';
$CommentsPerPage = '10';
//Limit words on news on homepage // 0 - to display full text
$NewsWordLimit = '40';

// Sort user comments: 1 - ID , 2 - newer , 3 - older
$SortComments = '1'; 

//Auto link in comments: 1 - allow, 2 - show plain text, 3 - remove all links
$AutoLinkComments = '1';
//If links removed, replace all LINKS with following text:
$AutoLinkTextReplace = '';
//Display full or short urls: 1 - full, 2 - short 
$AutoLinkFull = '0';

//Show hero stats on user page (favorite hero, hero with most kills, deaths, assists...)
$ShowUserHeroStats = '0';

$UserRegistration = '1';
$AllowComments = '1';

//Allow users to upload avatar image
$AllowUploadAvatar = '1';
//Max image size in pixels (default: 320px, quality: 85)
$MaxImageSize = '320';
$ImageQuality = '85';

$RecentGames = '1';
$TotalRecentGames = '5';

$ScoreStart = '1000';
$ScoreWins = '5';
$ScoreLosses = '3';
$ScoreDisc = '10';

//Enable/Disable Ban reports and appeals
$BanReports = '1';
$BanAppeals = '1';

//Add report user link on user page
$ReportUserLink = '1';

//After how much time a user can write next report
$BanReportTime = '180';

//How many games to update at once
$updateGames = '50';
//CronJob Update Games
$updateGamesCron = '10';

//When user register: 1 - user must confirm registration via email, 0 - instant activation
$UserActivation = '0';

//Get heroes data from playdota website
$PlayDotaHeroes = '0';

$MaxPaginationLinks = '2';

//Show fastest and longest game won
$ShowLongFastGameWon = '0';

$TopPage = '1';
$HeroesPage = '1';
$ItemsPage = '1';
$BansPage = '1';
$WarnPage = '0';
$AdminsPage = '1';
$SafelistPage = '0';
$MemberListPage = '1';
$GuidesPage = '0';
$AboutUs = '0';

$ShowMembersCountry = '1';

//Allow comparing players
$ComparePlayers = '1';
$MaxPlayersToCompare = '10';

//Show or hide (1/0) empty slots (empty username, or left time = 0 ... )
$HideEmptySlots = '1';

//Minimum game duration > 5*60 = 5 min (or 300 sec) 
//Only games with defined time (longer then $MinDuration ) will be counted in the statistics

$MinDuration = 5*60;

//Time a player leaves before the end of the game, which loses points ($ScoreDisc)
//Eg. if the user leaves the game 5 minutes before game end he will receive negative points -10
// $LeftTimePenalty = '300; in seconds (300 = 5 min), default
$LeftTimePenalty = '300';

//Enable/disable info about time to create page and total queries on every page
$pageGen = '1'; 
//Enable error reportings
$_debug = '0';

$TimeZone = 'Europe/Belgrade';

//Gamelist patch support
$GameListPatch = '0';

$OS_INSTALLED = '0';
?>