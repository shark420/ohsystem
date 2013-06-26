<?php  
if ( !isset($website ) ) { header('HTTP/1.1 404 Not Found'); die; }  

/* 
  Translated by: ICanHasGrief 
  http://www.codelain.com/forum/index.php?action=profile;u=92608
*/

$lang = array();  
    
$lang["home"]       = "Home";  
$lang["top"]        = "Beste Spieler";  
$lang["game_archive"]  = "Spiele";  
$lang["media"]      = "Infos";  
$lang["guides"]     = "Tutorials";  
$lang["heroes"]     = "Helden";  
$lang["heroes_vote"]= "Helden Wahl";  
$lang["item"]       = "Gegenstand";  
$lang["items"]      = "Gegenstände";  
$lang["bans"]       = "Verbannte Spieler";  
$lang["all_bans"]   = "Alle gebannten Spieler";  
$lang["ban_report"] = "Ban beantragen";  
$lang["ban_appeal"] = "Unban beantragen";  
$lang["report_user"] = "Beschwerde einreichen";  
$lang["warn"]       = "Warnungen";  
$lang["warned"]     = "Verwarnt";  
$lang["expire"]     = "Auslauf";  
$lang["search"]     = "Suche";  
$lang["search_players"] = "Suche Spieler...";  
$lang["admins"]     = "Administratoren";  
$lang["safelist"]   = "Gesicherte Spieler";  
$lang["about_us"]   = "Über uns";  
$lang["members"]    = "Mitglieder";  
    
$lang["username"]   = "Spieler";  
    
$lang["recent_games"]   = "Vergangene Spiele";  
$lang["recent_news"]    = "Vergangene News";  
    
$lang["profile"]         = "Profile";  
$lang["admin_panel"]     = "Admin Bereich";  
$lang["logout"]          = "Abmelden &times; ";  
$lang["login_register"]  = "Anmelden/Registrieren";  
$lang["login_fb_info"]   = "Klick auf den obrigen Button um sich via FB einzuloggen";  
$lang["total_comments"]  = "Kommentare";  
$lang["succes_registration"]  = "Du hast dich erfolgreich registriert!";  
    
$lang["profile_changed"]  = "Profil wurde erfolgreich aktualisiert!";  
$lang["password_changed"] = "Passwort wurde erfolgreich aktualisiert!";  
    
//APPEAL  
$lang["verify_appeal"]      = "Verefiziere den Spieler";  
$lang["verify_appeal_info"] = "Gib den Namen des Spielers ein um zu kontrollieren das er gebannt ist";  
$lang["appeal_here"]        = "Du kannst eine Aufhebung des Bannes hier beantragen: ";  
$lang["here"]               = "Klick mich";  
$lang["you_must_be"]        = "Du musst";  
$lang["logged_in"]          = "eingeloggt sein";  
$lang["to_appeal"]          = "um eine Aufhebung eines Bans zu beantragen. ";  
$lang["appeal_ban_date"]    = "Dieser Spieler wurde gebannt am";  
$lang["was_banned"]         = "Gebannt von";  
$lang["appeal_for"]         = "Aufhebung des Bannes für";  
$lang["subject"]            = "Typ";  
$lang["your_message"]       = "Deine Mitteilung";  
$lang["game_url"]           = "Spiel URL";  
$lang["replay_url"]         = "Replay URL";  
    
//REPORT  
$lang["report_player"]      = "Beschwerde einreichen über";  
$lang["report_reason"]      = "Grund der Beschwerde";  
$lang["report_submit"]      = "Beschwerde einreichen";  
$lang["error_report_player"]   = "Der Spieler Name ist zu kurz.";  
$lang["error_report_subject"]  = "Der Grund ist zu kurz.";  
$lang["error_report_reason"]   = "Der Text ist zu kurz.";  
$lang["error_no_player"]       = "Unsere Datenbank enthält keinerlei Informationen zu diesem Spieler";  
$lang["error_already_banned"]  = "Dieser Spieler ist bereits gebannt";   
$lang["error_report_login"]    = "Du musst eingeloggt sein um eine Beschwerde einzureichen,";  
$lang["error_report_time"]     = "Du kannst nicht so schnell hintereinander eine Beschwerden einreichen. Bitte warte einen Augenblick.";  
$lang["error_report_time2"]    = "Du kannst nicht so schnell hintereinander eine Aufhebung beantragen. Bitte warte einen Augenblick.";  
    
$lang["appeal_successfull"]    = "Aufhebung des Bans erfolgreich eingegangen. Bitte seien Sie geduldig bis wir Ihre Aufhebung bearbeitet haben."; 
$lang["report_successfull"]    = "Beschwerde über einen Spieler erfolgreich eingegangen. Bitte seien Sie geduldig bis wir Ihre Beschwerde bearbeitet haben.";  
    
//Time played. Ex. 10h 23m 16s  
$lang["h"]             = "h ";  
$lang["m"]             = "m ";  
$lang["s"]             = "s ";  
    
//Hero stats  
$lang["time_played"]             = "Spiel Zeit";  
$lang["average_loading"]         = "Durchschnittliche Lade Zeit";  
$lang["total_loading"]           = "Gesammte Ladezeit";  
$lang["seconds"]                 = "sek.";  
$lang["s"]                       = "s"; //seconds short  
$lang["favorite_hero"]           = "Favorisierter Held:";  
$lang["most_wins"]               = "Meisten gewonnen:";  
$lang["played"]                  = "gespielt";  
    
//Seconds  
$lang["error_sec"]             = "sek.";  
    
$lang["game"]       = "Spiel";  
$lang["duration"]   = "Zeit";  
$lang["type"]       = "Typ";  
$lang["date"]       = "Datum";  
$lang["map"]        = "Karte";  
$lang["creator"]    = "Ersteller";  
    
$lang["hero"]    = "Held";  
$lang["player"]  = "Spieler";  
$lang["kda"]     = "K/D/A";  
$lang["cdn"]     = "C/D/N";  
$lang["trc"]     = "T/R/C";  
$lang["gold"]    = "Gold";  
$lang["left"]    = "Verlassen";  
$lang["sent_winner"]    = "Sentinel hat gewonnen";  
$lang["scou_winner"]    = "Scourge hat gewonnen";  
$lang["sent_loser"]     = "Sentinel hat verloren";  
$lang["scou_loser"]     = "Scourge hat verloren";  
$lang["draw_game"]      = "Unentschiedenes Spiel";  
    
$lang["most_kills"]      = "Die meisten Kills:";  
$lang["most_assists"]    = "Die meisten Assists:";  
$lang["most_deaths"]     = "Die meisten Deaths:";  
$lang["top_ck"]          = "Die meisten Creep Kills:";  
$lang["top_cd"]          = "Die meisten Creep Denies:";  
    
$lang["score"]    = "Punkte";  
$lang["games"]    = "Spiele";  
$lang["wld"]     = "W/L/D";  
$lang["wl"]     = "W/L";  
$lang["tr"]     = "T/R";  
    
$lang["sortby"]     = "Sortieren nach:";  
$lang["wins"]       = "Gewonnen";  
$lang["losses"]     = "Verloren";  
$lang["draw"]       = "Unentschieden";  
    
$lang["kills"]       = "Kills";  
$lang["player_name"] = "Spielrname";  
$lang["deaths"]      = "Deaths";  
$lang["assists"]     = "Assists";  
$lang["ck"]          = "Creep Kills";  
$lang["cd"]          = "Creep Denies";  
$lang["nk"]          = "Neutral Kills";  
    
$lang["towers"]      = "Towers";  
$lang["rax"]         = "Rax";  
$lang["neutrals"]    = "Neutrals";  
$lang["submit"]          = "Abschicken";  
    
$lang["page"]          = "Seite";  
$lang["pageof"]        = "von";  
$lang["total"]         = "Insgesammt";  
$lang["next_page"]     = "Nächste Seite";  
$lang["previous_page"] = "Vorrige Seite";  
    
$lang["fastest_game"]   = 'Schnellstes Spiel gewonnen';  
$lang["longest_game"]   = 'Längstes Spiel gewonnen';  
$lang["show_hero_history"]    = "Zeige alle Spiele mit diesem Helden";
    
$lang["game_history"]         = "Vergangene Spiele:";  
$lang["user_game_history"]    = "Vergangene Spiele des SPielers";  
$lang["best_player"]          = "Bester Spieler: ";  
    
$lang["download_replay"]      = "Replay herunterladen";  
$lang["view_gamelog"]         = "Siehe Gamelog";  
    
$lang["win_percent"]          = "Win %";  
$lang["wl_percent"]           = "W/L%";  
$lang["kd_ratio"]             = "K/D Verhältniss";  
$lang["kd"]                   = "K/D";  
$lang["kpg"]                  = "KPG";  
$lang["kills_per_game"]       = "Kills pro Spiel";  
$lang["dpg"]                  = "DPG";  
$lang["apg"]                  = "APG";  
$lang["assists_per_game"]     = "Assists pro Spiel";  
$lang["ckpg"]                 = "CKPG";  
$lang["creeps_per_game"]      = "Creep kills pro Spiel";  
$lang["cdpg"]                 = "CDPK";  
$lang["denies_per_game"]      = "Denies pro Spiel";  
$lang["deaths_per_game"]      = "Deaths pro Spiel";  
$lang["npg"]                  = "NPG";  
$lang["neutrals_per_game"]    = "Neutrals pro Spiel";  
$lang["search_results"]       = "Spieler suche für: ";  
$lang["user_not_found"]       = "Spieler nicht gefunden";  
$lang["left_info"]            = "Wie oft ein Spieler vor dem Ende eines Spiels geleavt ist";  
    
$lang["admin"]       = "Administrator";  
$lang["server"]      = "Server";  
$lang["voucher"]     = "Erhalten von";  
    
$lang["banned"]     = "VERBANNT";  
$lang["reason"]     = "Grund";  
$lang["game_name"]  = "Spiel Name";  
$lang["bannedby"]   = "Verbannt von";  
$lang["leaves"]     = "Verlassen";  
$lang["stayratio"]     = "Stay Verhältniss";  
$lang["leaver"]        = "Leaver";  
$lang["streak"]        = "Streak";  
$lang["longest_streak"]= "Längster Streak";  
$lang["losing_streak"] = "Longest Losing Streak"; 
$lang["zero_deaths"]   = "The total number of games where the player has 0 deaths"; 
    
$lang["comments"]             = "Kommentar";  
$lang["add_comment"]          = "Kommentar hinzufügen";  
$lang["add_comment_button"]   = "Kommentar hinzufügen";  
$lang["reply"]                = "[antworten]";  
    
$lang["error_comment_not_allowed"]   = "Kommentare hinzufügen ist in diesem Post nicht erlaubt";  
$lang["error_invalid_form"]          = "Falsche Form";  
$lang["error_text_char"]             = "Der Text ist zu kurz";  
    
$lang["gamestate_priv"]       = "PRIV";  
$lang["gamestate_pub"]        = "PUB";  
    
//Login / Registration  
$lang["login"]       = "Anmelden";  
$lang["logged_as"]   = "Angemeldet als ";  
$lang["email"]       = "E-mail";  
$lang["avatar"]      = "Avatar";  
$lang["location"]    = "Ort";  
$lang["realm"]       = "Realm";  
$lang["website"]     = "Homepage";  
$lang["gender"]      = "Geschlecht"; 
$lang["language"]    = "Sprache"; 
$lang["male"]        = "Männlich";  
$lang["female"]      = "Weiblich";  
$lang["password"]    = "Passwort";  
$lang["register"]    = "Registrieren";  
$lang["username"]    = "Spielername";  
$lang["confirm_password"]       = "Passwort bestätigen";  
$lang["change_password"]        = "Passwort ändern";  
$lang["change_password_info"]   = "Klick auf die Box um dein Passwort zu ändern";  
$lang["comment_not_logged"]     = "Du musst eingeloggt sein um ein Kommentar zu verfassen";  
$lang["acc_activated"]          = "Account wurde erfolgreich aktiviert";  
$lang["invalid_link"]           = "Dieser Link existiert nicht oder ist schon ausgelaufen";  
    
//Heroes and items  
$lang["hero"]   = "Held";  
$lang["description"]     = "Beschreibung";  
$lang["stats"]           = "Stats";  
$lang["skills"]          = "Fähigkeiten";  
    
$lang["search"]          = "Suche";  
$lang["search_bans"]     = "Suche bans...";  
$lang["search_members"]  = "Suche Mitglieder...";  
$lang["search_heroes"]   = "Suche Helden...";  
$lang["search_items"]    = "Suche Gegenstände...";  
    
//Errors  
$lang["error_email"]      = "E-mail Adresse existiert nicht";  
$lang["error_short_pw"]   = "Das Passwort ist zu kurz";  
$lang["error_passwords"]  = "Das Passwörter stimmen nihct überein";  
$lang["error_inactive_acc"]   = "Account wurde noch nicht aktiviert";  
$lang["error_invalid_login"]  = "Falsche E-Mail oder falsches Passwort";  
$lang["error_short_un"]   = "E-Mail oder Passwort ist zu kurz";  
$lang["error_un_taken"]   = "Der Username existiert bereits";  
$lang["error_username"]   = "Flascher Username";  
$lang["error_email_taken"]= "Die E-mail Adresse ust bereits vergeben";  
    
//Email  
$lang["email_charset"] = "UTF-8";  
$lang["email_subject_activation"] = "Account Aktivierung";  
$lang["email_from"] = "no_reply@openstats.iz.rs";  
$lang["email_from_full"] = "OpenStats";  
    
//Email text  
$lang["email_activation1"] = "Hallo";  
$lang["email_activation2"] = "Du hast dich erfolgreich auf der StatsPage angemeldet ";  
$lang["email_activation3"] = "Klicke auf den folgenden Link um deinen Account zu verifizieren und zu bestätigen";  
    
//GAME LOG  
$lang["game_log"]            = "Spiel Log";  
$lang["log_player"]          = "Spieler";  
$lang["log_priv"]            = "[Priv]";  
$lang["log_ally"]            = "[Allies]";  
$lang["log_first_blood"]     = "für erstes Blut";  
$lang["log_suicide"]         = "hat sich selbst getötet!";  
$lang["log_denied_teammate"] = "killte seinen Mitspieler";  
$lang["log_level"]           = "Stufe";  
$lang["log_tower"]           = "Tower";  
$lang["log_barracks"]        = "Rax";  
    
$lang["404_error"]           = "Oops, Seite nicht gefunden";  
    
//VOTES  
$lang["vote_title"]          = "Wähle deinen Fevoriten";  
$lang["votes_won"]           = "Wahl Gewonnen";  
$lang["votes_lost"]          = "Wahl Verloren";  
$lang["votes_total"]         = "Alle Stimmen";  
$lang["votes_best"]          = "Bester";  
$lang["vote_vs"]             = "gegen";  
$lang["vote_sort"]           = "Abschicken";  
$lang["vote_results"]        = "Wahl Ergebnis";  
$lang["vote_back"]           = "Zurück zur Wahö";  
    
$lang["vote_won"]           = "Gewonnen:";  
$lang["vote_lost"]          = "Verloren:";  
$lang["vote_again"]         = "Nochmal wählen?";  
    
$lang["vote_error1"]        = "Klick bitte auf die Helden um zu wählen.";  
$lang["vote_display"]       = "Ergebnisse anzeigen";  
    
$lang["read_more"]          = "...[mehr]";  
    
$lang["upload_image"]      = "Bild hochladen";  
$lang["remove_avatar"]     = "Avatar entfernen";  
    
//Gamelist patch  
$lang["current_games"]      = "Aktuelle Spiele";  
$lang["refresh"]            = "Aktualisieren";  
$lang["slots"]              = "Slots / Total";  
$lang["empty"]              = "Leer";  
$lang["ms"]                 = "ms";  
    
//Members  
$lang["joined"]         = "Beigetreten";  
$lang["user_info"]      = "Info";  
    
$lang["choose"]         = "Wähle";  
$lang["all_guides"]     = "---All guides---";  
    
//Compare players  
$lang["compare_back"]         = "&laquo; Zurück zu Top Spielern";  
$lang["compare_list"]         = "Liste";  
$lang["compare_list_empty"]   = "Liste ist leer";  
$lang["compare_compare"]      = "Vergleichen";  
$lang["compare_add"]          = "Zur Liste hinzufügen";  
$lang["compare_clear"]        = "Liste löschen";  
$lang["compare_remove_player"]   = "Entferne Spieler von der Liste?";  
$lang["compare_players"]      = "Spieler vergleichen";  
$lang["compare_empty_info"]   = "Die Liste der Spieler zum vergleichen ist leer";  
$lang["overall"]              = "Grafik:";  
$lang["stay"]              = "Stay";  
?>  