<?php
if ( !isset($website ) ) { header('HTTP/1.1 404 Not Found'); die; }

$lang = array();

$lang["home"]       = "Početak";
$lang["top"]        = "Top Igrači";
$lang["game_archive"]  = "Istorija igara";
$lang["media"]      = "Ostalo";
$lang["guides"]     = "Tutorijal";
$lang["heroes"]     = "Heroji";
$lang["heroes_vote"]= "Heroji glasanje";
$lang["item"]       = "Predmet";
$lang["items"]      = "Predmeti";
$lang["bans"]       = "Banovi";
$lang["all_bans"]   = "Svi banovi";
$lang["ban_report"] = "Prijava bana";
$lang["ban_appeal"] = "Žalba na ban";
$lang["report_user"] = "Prijavi korisnika";
$lang["warn"]       = "Upozorenja";
$lang["warned"]     = "Upozoren";
$lang["expire"]     = "Ističe";
$lang["search"]     = "Pretraga";
$lang["search_players"] = "Pretraga igraač...";
$lang["admins"]     = "Admini";
$lang["safelist"]   = "Sigurna lista";
$lang["about_us"]   = "O Nama";
$lang["members"]    = "Članovi";

$lang["username"]   = "Korisničko ime";

$lang["recent_games"]   = "Poslednje igre";
$lang["recent_news"]    = "Poslednje vesti";

$lang["profile"]         = "Profil";
$lang["admin_panel"]     = "Admin Panel";
$lang["logout"]          = "Odjavi se &times; ";
$lang["login_register"]  = "Prijava/Registracija";
$lang["login_fb_info"]   = "Klikni na dugme iznad da se prijaviš sa tvojim FB nalogom";
$lang["total_comments"]  = "komentar(a)";
$lang["succes_registration"]  = "Uspešno ste se registrovali";

$lang["profile_changed"]  = "Profil je uspešno ažuriran";
$lang["password_changed"] = "Lozinka je uspešno promenjena";

//APPEAL
$lang["verify_appeal"]      = "Provera Banovanog Naloga";
$lang["verify_appeal_info"] = "Upiši ime ispod kako bi proverili da li korisnik banovan";
$lang["appeal_here"]        = "Možeš se žaliti na ovaj ban ";
$lang["here"]               = "ovde";
$lang["you_must_be"]        = "Potrebno je da se";
$lang["logged_in"]          = "prijaviš";
$lang["to_appeal"]          = "kako bi napisao žalbu";
$lang["appeal_ban_date"]    = "Ovaj nalog je banovan";
$lang["was_banned"]         = "banovao je";
$lang["appeal_for"]         = "Žalba na ban za korisnika";
$lang["subject"]            = "Naslov";
$lang["your_message"]       = "Tvoja poruka ovde";
$lang["game_url"]           = "URL adresa igre";
$lang["replay_url"]         = "URL adresa za replay";

//REPORT
$lang["report_player"]      = "Prijavi igrača";
$lang["report_reason"]      = "Razlog prijave igrača+";
$lang["report_submit"]      = "Dodaj prijavu";
$lang["error_report_player"]   = "Naziv igrača nema dovoljno znakova";
$lang["error_report_subject"]  = "Naslov nema dovoljno znakova";
$lang["error_report_reason"]   = "Tekst nema dovoljno znakova";
$lang["error_no_player"]       = "Nema podataka u bazi za ovog igrača";
$lang["error_already_banned"]  = "Taj korisnik je već banovan"; 
$lang["error_report_login"]    = "Potrebno je da se prijaviš da bi napisao prijavu";
$lang["error_report_time"]     = "Molimo sačekajte. Ne možete tako brzo pisati prijave.";
$lang["error_report_time2"]    = "Molimo sačekajte. Ne možete tako brzo pisati žalbe.";

$lang["appeal_successfull"]    = "Žalba uspešno dodata.";
$lang["report_successfull"]    = "Prijava uspešno dodata.";

//Time played. Ex. 10h 23m 16s
$lang["h"]             = "h ";
$lang["m"]             = "m ";
$lang["s"]             = "s ";

//Hero stats
$lang["time_played"]             = "Vreme igranja";
$lang["average_loading"]         = "Prosečno vreme učitavanja";
$lang["total_loading"]           = "Ukupno vreme učitavanja";
$lang["seconds"]                 = "sek.";
$lang["s"]                       = "s"; //seconds short
$lang["favorite_hero"]           = "Omiljeni heroji:";
$lang["most_wins"]               = "Najviše pobeda:";
$lang["played"]                  = "Igrano";

//Seconds
$lang["error_sec"]             = "sek.";

$lang["game"]       = "Igra";
$lang["duration"]   = "Trajanje";
$lang["type"]       = "Tip";
$lang["date"]       = "Datum";
$lang["map"]        = "Mapa";
$lang["creator"]    = "Napravio";

$lang["hero"]    = "Heroj";
$lang["player"]  = "Igrač";
$lang["kda"]     = "K/D/A";
$lang["cdn"]     = "C/D/N";
$lang["trc"]     = "T/R/C";
$lang["gold"]    = "Zlato";
$lang["left"]    = "Napustio";
$lang["sent_winner"]    = "Sentinel Pobedio";
$lang["scou_winner"]    = "Scourge Pobedio";
$lang["sent_loser"]     = "Sentinel Izgubio";
$lang["scou_loser"]     = "Scourge Izgubio";
$lang["draw_game"]      = "Nerešena igra";

$lang["most_kills"]      = "Najviše Ubistva:";
$lang["most_assists"]    = "Najviše Asistencija:";
$lang["most_deaths"]     = "Najviše Smrti:";
$lang["top_ck"]          = "Top Creep Kills:";
$lang["top_cd"]          = "Top Creep Denies:";

$lang["score"]    = "Bodovi";
$lang["games"]    = "Igre";
$lang["wld"]     = "W/L/D";
$lang["wl"]     = "W/L";
$lang["tr"]     = "T/R";

$lang["sortby"]     = "Sortiraj:";
$lang["wins"]       = "Pobede";
$lang["losses"]     = "Porazi";
$lang["draw"]       = "Nerešeno";

$lang["kills"]       = "Ubistva";
$lang["player_name"] = "Naziv igrača";
$lang["deaths"]      = "Smrti";
$lang["assists"]     = "Asistencijre";
$lang["ck"]          = "Creep Kills";
$lang["cd"]          = "Creep Denies";
$lang["nk"]          = "Neutral Kills";

$lang["towers"]      = "Kule";
$lang["rax"]         = "Rax";
$lang["neutrals"]    = "Neutrals";
$lang["submit"]          = "Dodaj";

$lang["page"]          = "Strana";
$lang["pageof"]        = "od";
$lang["total"]         = "ukupno";
$lang["next_page"]     = "Sledeća strana";
$lang["previous_page"] = "Prethodna strana";

$lang["fastest_game"]   = 'Najbrža pobeda u igri';
$lang["longest_game"]   = 'Najduža pobeda u igri';

$lang["game_history"]         = "Istorijat igara:";
$lang["user_game_history"]    = "Istorijat igara igrača";
$lang["best_player"]          = "Najbolji igrač: ";
$lang["show_hero_history"]    = "Prikaži sve igre sa ovim herojem";

$lang["download_replay"]      = "Download replay";
$lang["view_gamelog"]         = "Pogledaj Gamelog";

$lang["win_percent"]          = "Pobede %";
$lang["wl_percent"]           = "W/L%";
$lang["kd_ratio"]             = "K/D Odnos";
$lang["kd"]                   = "K/D";
$lang["kpg"]                  = "KPG";
$lang["kills_per_game"]       = "Ubistva po igri";
$lang["dpg"]                  = "DPG";
$lang["apg"]                  = "APG";
$lang["assists_per_game"]     = "Asistencija po igri";
$lang["ckpg"]                 = "CKPG";
$lang["creeps_per_game"]      = "Ubijeno krepova po igri";
$lang["cdpg"]                 = "CDPK";
$lang["denies_per_game"]      = "Denies per game";
$lang["deaths_per_game"]      = "Deaths per game";
$lang["npg"]                  = "NPG";
$lang["neutrals_per_game"]    = "Neutrals per game";
$lang["search_results"]       = "Rezultati pretrage za: ";
$lang["user_not_found"]       = "Korisnik nije pronađen";
$lang["left_info"]            = "Koliko puta je igrač napustio igru pre vremena";

$lang["admin"]       = "Admin";
$lang["server"]      = "Server";
$lang["voucher"]     = "Vaučer";

$lang["banned"]     = "BANOVAN";
$lang["reason"]     = "Razlog";
$lang["game_name"]  = "Naziv igre";
$lang["bannedby"]   = "Banovao";
$lang["leaves"]     = "Izašao";
$lang["stayratio"]     = "Stay ratio";
$lang["leaver"]        = "Leaver";
$lang["streak"]        = "Streak";
$lang["longest_streak"]= "Uzastopne pobede";
$lang["losing_streak"] = "Uzastopni porazi";
$lang["zero_deaths"]   = "Ukupan broj igara gde igrač ima 0 smrti";

$lang["comments"]             = "Komentari";
$lang["add_comment"]          = "Dodaj komentar";
$lang["add_comment_button"]   = "Dodaj komentar";
$lang["reply"]                = "[odgovor]";

$lang["error_comment_not_allowed"]   = "Pisanje komentara nije dozvoljeno za ovaj post";
$lang["error_invalid_form"]          = "Pogrešna forma";
$lang["error_text_char"]             = "Tekst nema dovoljno znakova";

$lang["gamestate_priv"]       = "PRIV";
$lang["gamestate_pub"]        = "PUB";

//Login / Registration
$lang["login"]       = "Prijava";
$lang["logged_as"]   = "Prijavljen kao ";
$lang["email"]       = "E-mail";
$lang["avatar"]      = "Avatar";
$lang["location"]    = "Lokacija";
$lang["realm"]       = "Realm";
$lang["website"]     = "Website";
$lang["gender"]      = "Pol";
$lang["male"]        = "Muško";
$lang["female"]      = "Žensko";
$lang["password"]    = "Lozinka";
$lang["register"]    = "Registracija";
$lang["username"]    = "Korisničko ime";
$lang["language"]    = "Jezik"; 
$lang["confirm_password"]       = "Potvrda lozinke";
$lang["change_password"]        = "Promena lozinke";
$lang["change_password_info"]   = "Označi ovo ukoliko menjaš lozinku";
$lang["comment_not_logged"]     = "Potrebno je da se prijaviš za pisanje komentara.";
$lang["acc_activated"]          = "Nalog je uspešno aktiviran. Sada možeš da se prijaviš.";
$lang["invalid_link"]           = "Link nije validan ili je istekao.";

//Heroes and items
$lang["hero"]   = "Heroj";
$lang["description"]     = "Opis";
$lang["stats"]           = "Stats";
$lang["skills"]          = "Skills";

$lang["search"]          = "Pretraga";
$lang["search_bans"]     = "Pretraga bana...";
$lang["search_members"]  = "Pretraga Članova...";
$lang["search_heroes"]   = "Pretraga heroja...";
$lang["search_items"]    = "Pretraga predmeta...";

//Errors
$lang["error_email"]      = "E-mail adresa nije ispravna";
$lang["error_short_pw"]   = "Polje lozinka nema dovoljno znakova";
$lang["error_passwords"]  = "Lozinke se ne poklapaju";
$lang["error_inactive_acc"]   = "Nalog još uvek nije aktiviran";
$lang["error_invalid_login"]  = "Pogrešna email adresa ili lozinka";
$lang["error_short_un"]   = "Polje Korisničko ime nema dovoljno znakova";
$lang["error_un_taken"]   = "Korisničko ime već postoji";
$lang["error_username"]   = "Pogrešno korisničko ime";
$lang["error_email_taken"]= "E-mail adresa već postoji";

//Email
$lang["email_charset"] = "UTF-8";
$lang["email_subject_activation"] = "Aktivacija naloga";
$lang["email_from"] = "no_reply@openstats.iz.rs";
$lang["email_from_full"] = "OpenStats";

//Email text
$lang["email_activation1"] = "Pozdrav";
$lang["email_activation2"] = "Uspešno si se registrovao na sajt ";
$lang["email_activation3"] = "Klikni na sledeći link da potvrdiš tvoju email adresu i aktiviraš nalog";

//GAME LOG
$lang["game_log"]            = "Game Log";
$lang["log_player"]          = "Igrač";
$lang["log_priv"]            = "[Priv]";
$lang["log_ally"]            = "[Allies]";
$lang["log_first_blood"]     = "for first blood";
$lang["log_suicide"]         = "has killed himself!";
$lang["log_denied_teammate"] = "denied his teammate";
$lang["log_level"]           = "level";
$lang["log_tower"]           = "tower";
$lang["log_barracks"]        = "barracks";

$lang["404_error"]           = "Ooops, stranica nije pronađena";

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

$lang["upload_image"]      = "Dodaj sliku";
$lang["remove_avatar"]     = "Ukloni avatara";

//Gamelist patch
$lang["current_games"]      = "Trenutne igre";
$lang["refresh"]            = "Osveži";
$lang["slots"]              = "Slotovi / Ukupno";
$lang["empty"]              = "Prazno";
$lang["ms"]                 = "ms";

//Members
$lang["joined"]         = "Pridružio";
$lang["user_info"]      = "Info";

$lang["choose"]         = "Izaberi";
$lang["all_guides"]     = "---All guides---";

//Compare players
$lang["compare_back"]         = "&laquo; Nazad na Top listu";
$lang["compare_list"]         = "Lista";
$lang["compare_list_empty"]   = "Lista je prazna";
$lang["compare_compare"]      = "Uporedi";
$lang["compare_add"]          = "Dodaj u listu za upoređivanje";
$lang["compare_clear"]        = "Očisti listu";
$lang["compare_remove_player"]   = "Ukloni igrača iz liste?";
$lang["compare_players"]      = "UPOREDI IGRAČE";
$lang["compare_empty_info"]   = "Tvoja lista za upoređivanje igrača je prazna";
$lang["overall"]              = "Ukupno";
$lang["stay"]              = "Stay";
?>