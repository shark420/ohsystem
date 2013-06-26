<?php
if ( !isset($website ) ) { header('HTTP/1.1 404 Not Found'); die; }

$lang = array();

$lang["home"]       = "Home";
$lang["top"]        = "Top Players";
$lang["game_archive"]  = "Games History";
$lang["media"]      = "Misc";
$lang["guides"]     = "Guides";
$lang["heroes"]     = "Heroes";
$lang["heroes_vote"]= "Heroes Vote";
$lang["item"]       = "Item";
$lang["items"]      = "Items";
$lang["bans"]       = "Bans";
$lang["all_bans"]   = "All Bans";
$lang["ban_report"] = "Ban Report";
$lang["ban_appeal"] = "Ban Appeal";
$lang["report_user"] = "Report user";
$lang["warn"]       = "Warns";
$lang["warned"]     = "Warned";
$lang["expire"]     = "Expire";
$lang["search"]     = "Search";
$lang["search_players"] = "Search players...";
$lang["admins"]     = "Admins";
$lang["safelist"]   = "Safelist";
$lang["about_us"]   = "About Us";
$lang["members"]    = "Members";

$lang["username"]   = "Username";

$lang["recent_games"]   = "Recent Games";
$lang["recent_news"]    = "Recent News";

$lang["profile"]         = "Profile";
$lang["admin_panel"]     = "Admin Panel";
$lang["logout"]          = "Logout &times; ";
$lang["login_register"]  = "Login/Register";
$lang["login_fb_info"]   = "Click on the button above to sign in with your FB account";
$lang["total_comments"]  = "comment(s)";
$lang["succes_registration"]  = "You have successfully registered";

$lang["profile_changed"]  = "Profile has been successfully updated";
$lang["password_changed"] = "Password has been successfully updated";

//APPEAL
$lang["verify_appeal"]      = "Verify Banned Account";
$lang["verify_appeal_info"] = "Enter the name below to check whether the user is banned";
$lang["appeal_here"]        = "You can appeal this ban ";
$lang["here"]               = "here";
$lang["you_must_be"]        = "You must be";
$lang["logged_in"]          = "logged in";
$lang["to_appeal"]          = "to appeal";
$lang["appeal_ban_date"]    = "This account was banned on";
$lang["was_banned"]         = "was banned by";
$lang["appeal_for"]         = "Ban Appeal for user";
$lang["subject"]            = "Subject";
$lang["your_message"]       = "Your message here";
$lang["game_url"]           = "Game URL";
$lang["replay_url"]         = "Replay URL";

//REPORT
$lang["report_player"]      = "Report player";
$lang["report_reason"]      = "Reason for reporting player";
$lang["report_submit"]      = "Submit Report";
$lang["error_report_player"]   = "Player name does not have enough characters";
$lang["error_report_subject"]  = "Subject does not have enough characters";
$lang["error_report_reason"]   = "Text does not have enough characters";
$lang["error_no_player"]       = "No information about this player in our database";
$lang["error_already_banned"]  = "This player is already banned"; 
$lang["error_report_login"]    = "You need to login to write a report";
$lang["error_report_time"]     = "You can't quickly write ban reports. Please wait";
$lang["error_report_time2"]    = "You can't quickly write ban appeals. Please wait";

$lang["appeal_successfull"]    = "Appeal successfully added.";
$lang["report_successfull"]    = "Report successfully added.";

//Time played. Ex. 10h 23m 16s
$lang["h"]             = "h ";
$lang["m"]             = "m ";
$lang["s"]             = "s ";

//Hero stats
$lang["time_played"]             = "Time Played";
$lang["average_loading"]         = "Average loading time";
$lang["total_loading"]           = "Total loading time";
$lang["seconds"]                 = "sec.";
$lang["s"]                       = "s"; //seconds short
$lang["favorite_hero"]           = "Favorite Hero:";
$lang["most_wins"]               = "Most Wins:";
$lang["played"]                  = "Played";

//Seconds
$lang["error_sec"]             = "sec.";

$lang["game"]       = "Game";
$lang["duration"]   = "Duration";
$lang["type"]       = "Type";
$lang["date"]       = "Date";
$lang["map"]        = "Map";
$lang["creator"]    = "Creator";

$lang["hero"]    = "Hero";
$lang["player"]  = "Player";
$lang["kda"]     = "K/D/A";
$lang["cdn"]     = "C/D/N";
$lang["trc"]     = "T/R/C";
$lang["gold"]    = "Gold";
$lang["left"]    = "Left";
$lang["sent_winner"]    = "Sentinel Winner";
$lang["scou_winner"]    = "Scourge Winner";
$lang["sent_loser"]     = "Sentinel Loser";
$lang["scou_loser"]     = "Scourge Loser";
$lang["draw_game"]      = "Draw Game";

$lang["most_kills"]      = "Most Kills:";
$lang["most_assists"]    = "Most Assists:";
$lang["most_deaths"]     = "Most Deaths:";
$lang["top_ck"]          = "Top Creep Kills:";
$lang["top_cd"]          = "Top Creep Denies:";

$lang["score"]    = "Score";
$lang["games"]    = "Games";
$lang["wld"]     = "W/L/D";
$lang["wl"]     = "W/L";
$lang["tr"]     = "T/R";

$lang["sortby"]     = "Sort by:";
$lang["wins"]       = "Wins";
$lang["losses"]     = "Losses";
$lang["draw"]       = "Draw";

$lang["kills"]       = "Kills";
$lang["player_name"] = "Player name";
$lang["deaths"]      = "Deaths";
$lang["assists"]     = "Assists";
$lang["ck"]          = "Creep Kills";
$lang["cd"]          = "Creep Denies";
$lang["nk"]          = "Neutral Kills";

$lang["towers"]      = "Towers";
$lang["rax"]         = "Rax";
$lang["neutrals"]    = "Neutrals";
$lang["submit"]          = "Submit";

$lang["page"]          = "Page";
$lang["pageof"]        = "of";
$lang["total"]         = "total";
$lang["next_page"]     = "Next page";
$lang["previous_page"] = "Previous page";

$lang["fastest_game"]   = 'Fastest Game Won';
$lang["longest_game"]   = 'Longest Game Won';

$lang["game_history"]         = "Game History:";
$lang["user_game_history"]    = "User Game History";
$lang["best_player"]          = "Best Player: ";
$lang["show_hero_history"]    = "Show all games with this hero";

$lang["download_replay"]      = "Download replay";
$lang["view_gamelog"]         = "View Gamelog";

$lang["win_percent"]          = "Win %";
$lang["wl_percent"]           = "W/L%";
$lang["kd_ratio"]             = "K/D Ratio";
$lang["kd"]                   = "K/D";
$lang["kpg"]                  = "KPG";
$lang["kills_per_game"]       = "Kills per game";
$lang["dpg"]                  = "DPG";
$lang["apg"]                  = "APG";
$lang["assists_per_game"]     = "Assists per game";
$lang["ckpg"]                 = "CKPG";
$lang["creeps_per_game"]      = "Creep kills per game";
$lang["cdpg"]                 = "CDPK";
$lang["denies_per_game"]      = "Denies per game";
$lang["deaths_per_game"]      = "Deaths per game";
$lang["npg"]                  = "NPG";
$lang["neutrals_per_game"]    = "Neutrals per game";
$lang["search_results"]       = "Search results for: ";
$lang["user_not_found"]       = "User not found";
$lang["left_info"]            = "How many times a player has left the game before the end of the game";

$lang["admin"]       = "Admin";
$lang["server"]      = "Server";
$lang["voucher"]     = "Voucher";

$lang["banned"]     = "BANNED";
$lang["reason"]     = "Reason";
$lang["game_name"]  = "Game Name";
$lang["bannedby"]   = "Banned by";
$lang["leaves"]     = "Leaves";
$lang["stayratio"]     = "Stay ratio";
$lang["leaver"]        = "Leaver";
$lang["streak"]        = "Streak";
$lang["longest_streak"]= "Winning Streak";
$lang["losing_streak"] = "Losing Streak";
$lang["zero_deaths"]   = "The total number of games where the player has 0 deaths";

$lang["comments"]             = "Comments";
$lang["add_comment"]          = "Add Comment";
$lang["add_comment_button"]   = "Add Comment";
$lang["reply"]                = "[reply]";

$lang["error_comment_not_allowed"]   = "Writing comments is not allowed for this post";
$lang["error_invalid_form"]          = "Invalid form";
$lang["error_text_char"]             = "Text does not have enough characters";

$lang["gamestate_priv"]       = "PRIV";
$lang["gamestate_pub"]        = "PUB";

//Login / Registration
$lang["login"]       = "Login";
$lang["logged_as"]   = "Logged as ";
$lang["email"]       = "E-mail";
$lang["avatar"]      = "Avatar";
$lang["location"]    = "Location";
$lang["realm"]       = "Realm";
$lang["website"]     = "Website";
$lang["gender"]      = "Gender";
$lang["male"]        = "Male";
$lang["female"]      = "Female";
$lang["password"]    = "Password";
$lang["register"]    = "Register";
$lang["username"]    = "Username";
$lang["language"]    = "Language";
$lang["confirm_password"]       = "Confirm Password";
$lang["change_password"]        = "Change Password";
$lang["change_password_info"]   = "Check this if you want to change the password";
$lang["comment_not_logged"]     = "You need to be logged in to post a comment.";
$lang["acc_activated"]          = "Account successfully activated. Now you can login.";
$lang["invalid_link"]           = "Link is not valid or expired.";

//Heroes and items
$lang["hero"]   = "Hero";
$lang["description"]     = "Description";
$lang["stats"]           = "Stats";
$lang["skills"]          = "Skills";

$lang["search"]          = "Search";
$lang["search_bans"]     = "Search bans...";
$lang["search_members"]  = "Search Members...";
$lang["search_heroes"]   = "Search heroes...";
$lang["search_items"]    = "Search items...";

//Errors
$lang["error_email"]      = "E-mail address is not valid";
$lang["error_short_pw"]   = "Field Password does not have enough characters";
$lang["error_passwords"]  = "Password and confirmation password do not match";
$lang["error_inactive_acc"]   = "Account is not activated yet";
$lang["error_invalid_login"]  = "Invalid e-mail or password";
$lang["error_short_un"]   = "Field Username does not have enough characters";
$lang["error_un_taken"]   = "Username already taken";
$lang["error_username"]   = "Invalid username";
$lang["error_email_taken"]= "E-mail already taken";

//Email
$lang["email_charset"] = "UTF-8";
$lang["email_subject_activation"] = "Account Activation";
$lang["email_from"] = "no_reply@openstats.iz.rs";
$lang["email_from_full"] = "OpenStats";

//Email text
$lang["email_activation1"] = "Hello";
$lang["email_activation2"] = "You have successfully registered to the site ";
$lang["email_activation3"] = "Click on the following link to confirm your email address and activate your account";

//GAME LOG
$lang["game_log"]            = "Game Log";
$lang["log_player"]          = "Player";
$lang["log_priv"]            = "[Priv]";
$lang["log_ally"]            = "[Allies]";
$lang["log_first_blood"]     = "for first blood";
$lang["log_suicide"]         = "has killed himself!";
$lang["log_denied_teammate"] = "denied his teammate";
$lang["log_level"]           = "level";
$lang["log_tower"]           = "tower";
$lang["log_barracks"]        = "barracks";

$lang["404_error"]           = "Oops, page not found";

//VOTES
$lang["vote_title"]          = "Vote for favorite";
$lang["votes_won"]           = "Votes Won";
$lang["votes_lost"]          = "Votes Lost";
$lang["votes_total"]         = "Total Votes";
$lang["votes_best"]          = "Best";
$lang["vote_vs"]             = "VS";
$lang["vote_sort"]           = "Submit";
$lang["vote_results"]        = "Vote Results";
$lang["vote_back"]           = "Back to Vote";

$lang["vote_won"]           = "Won:";
$lang["vote_lost"]          = "Lost:";
$lang["vote_again"]         = "Vote again?";

$lang["vote_error1"]        = "Please click on the hero to vote.";
$lang["vote_display"]       = "Display results";

$lang["read_more"]          = "...[more]";

$lang["upload_image"]      = "Upload image";
$lang["remove_avatar"]     = "Remove avatar";

//Gamelist patch
$lang["current_games"]      = "Current Games";
$lang["refresh"]            = "Refresh";
$lang["slots"]              = "Slots / Total";
$lang["empty"]              = "Empty";
$lang["ms"]                 = "ms";

//Members
$lang["joined"]         = "Joined";
$lang["user_info"]      = "Info";

$lang["choose"]         = "Choose";
$lang["all_guides"]     = "---All guides---";

//Compare players
$lang["compare_back"]         = "&laquo; Back to Top players";
$lang["compare_list"]         = "List";
$lang["compare_list_empty"]   = "List is empty";
$lang["compare_compare"]      = "Compare";
$lang["compare_add"]          = "Add to compare list";
$lang["compare_clear"]        = "Clear list";
$lang["compare_remove_player"]   = "Remove player from list?";
$lang["compare_players"]      = "Player Comparison";
$lang["compare_empty_info"]   = "Your list of players to compare is empty";
$lang["overall"]              = "Overall";
$lang["stay"]              = "Stay";
?>