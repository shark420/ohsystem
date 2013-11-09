/*
 
Copyright [2008] [Trevor Hogan]
 
Licensed under the Apache License, Version 2.0 (the "License");
you may not use this file except in compliance with the License.
You may obtain a copy of the License at
 
http://www.apache.org/licenses/LICENSE-2.0
 
Unless required by applicable law or agreed to in writing, software
distributed under the License is distributed on an "AS IS" BASIS,
WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
See the License for the specific language governing permissions and
limitations under the License.
 
CODE PORTED FROM THE ORIGINAL GHOST PROJECT: http://ghost.pwner.org/
 
*/
 
#include "ghost.h"
#include "util.h"
#include "config.h"
#include "language.h"
#include "socket.h"
#include "ghostdb.h"
#include "bnet.h"
#include "map.h"
#include "packed.h"
#include "savegame.h"
#include "gameplayer.h"
#include "gameprotocol.h"
#include "game_base.h"
#include "game.h"
#include "stats.h"
#include "statsdota.h"
#include "statsw3mmd.h"
 
#include <stdio.h>
#include <cmath>
#include <string.h>
#include <time.h>
#include <boost/thread.hpp>
 
//
// sorting classes
//
 
class CGamePlayerSortAscByPing
{
public:
        bool operator( ) ( CGamePlayer *Player1, CGamePlayer *Player2 ) const
        {
                return Player1->GetPing( false ) < Player2->GetPing( false );
        }
};
 
class CGamePlayerSortDescByPing
{
public:
        bool operator( ) ( CGamePlayer *Player1, CGamePlayer *Player2 ) const
        {
                return Player1->GetPing( false ) > Player2->GetPing( false );
        }
};
 
//
// CGame
//
 
CGame :: CGame( CGHost *nGHost, CMap *nMap, CSaveGame *nSaveGame, uint16_t nHostPort, unsigned char nGameState, string nGameName, string nOwnerName, string nCreatorName, string nCreatorServer, uint32_t nGameType ) : CBaseGame( nGHost, nMap, nSaveGame, nHostPort, nGameState, nGameName, nOwnerName, nCreatorName, nCreatorServer, nGameType ), m_DBBanLast( NULL ), m_Stats( NULL ) ,m_CallableGameAdd( NULL ), m_ForfeitTime( 0 ), m_ForfeitTeam( 0 ), m_EarlyDraw( false )
{
        m_DBGame = new CDBGame( 0, string( ), m_Map->GetMapPath( ), string( ), string( ), string( ), 0 );
 
        if( m_Map->GetMapType( ) == "w3mmd" )
                m_Stats = new CStatsW3MMD( this, m_Map->GetMapStatsW3MMDCategory( ) );
        else if( m_Map->GetMapType( ) == "dota" )
                m_Stats = new CStatsDOTA( this );
 
        m_LobbyLog.clear();
        m_GameLog.clear();
}
 
CGame :: ~CGame( )
{
        // autoban
        uint32_t EndTime = m_GameTicks / 1000;
        uint32_t Counter = 0;
        for( vector<CDBGamePlayer *> :: iterator i = m_DBGamePlayers.begin( ); i != m_DBGamePlayers.end( ); ++i )
        {
                if( IsAutoBanned( (*i)->GetName( ) ) )
                {
                        uint32_t VictimLevel = 0;
                        for( vector<CBNET *> :: iterator k = m_GHost->m_BNETs.begin( ); k != m_GHost->m_BNETs.end( ); ++k )
                        {
                               if( (*k)->GetServer( ) == (*i)->GetSpoofedRealm( ) )
                               {
                                       VictimLevel = (*k)->IsLevel( (*i)->GetName( ) );
                                       break;
                               }
                        }
 
                        uint32_t LeftTime = (*i)->GetLeft( );
                        // make sure that draw games will not ban people who didnt leave.
                        if( EndTime - LeftTime > 300 || m_EarlyDraw && LeftTime != EndTime )
                        {
                                if( m_EarlyDraw )
                                        Counter++;
                                if( Counter <= 2 && VictimLevel <= 2 )
                                {
                                        string Reason = "left at " + UTIL_ToString( LeftTime ) + "/" + UTIL_ToString( EndTime );
                                        m_GHost->m_Callables.push_back( m_GHost->m_DB->ThreadedBanAdd( (*i)->GetSpoofedRealm(), (*i)->GetName( ), (*i)->GetIP(), m_GameName, "PeaceMaker", Reason, 86400, ""  ) );
                                }
                        }
                }
        }
 
        if( m_CallableGameAdd && m_CallableGameAdd->GetReady( ) )
        {
 
                if (m_GHost->m_GameIDReplays)
                {
                        m_DatabaseID = m_CallableGameAdd->GetResult();
 
                }
                if( m_CallableGameAdd->GetResult( ) > 0 )
                {
                        CONSOLE_Print( "[GAME: " + m_GameName + "] saving player/stats data to database" );
 
                        // store the CDBGamePlayers in the database
 
                        for( vector<CDBGamePlayer *> :: iterator i = m_DBGamePlayers.begin( ); i != m_DBGamePlayers.end( ); ++i )
                                m_GHost->m_Callables.push_back( m_GHost->m_DB->ThreadedGamePlayerAdd( m_CallableGameAdd->GetResult(), (*i)->GetName( ), (*i)->GetIP( ), (*i)->GetSpoofed( ), (*i)->GetSpoofedRealm( ), (*i)->GetReserved( ), (*i)->GetLoadingTime( ), (*i)->GetLeft( ), (*i)->GetLeftReason( ), (*i)->GetTeam( ), (*i)->GetColour( ) ) );
 
                                if( m_Stats )
                                {
                                        m_Stats->Save( m_GHost, m_GHost->m_DB, m_CallableGameAdd->GetResult() );
                                }
                }
                else
                        CONSOLE_Print( "[GAME: " + m_GameName + "] unable to save player/stats data to database" );
 
                m_GHost->m_DB->RecoverCallable( m_CallableGameAdd );
                delete m_CallableGameAdd;
                m_CallableGameAdd = NULL;
        }
 
        for( vector<PairedBanCheck> :: iterator i = m_PairedBanChecks.begin( ); i != m_PairedBanChecks.end( ); ++i )
                m_GHost->m_Callables.push_back( i->second );
 
        for( vector<Pairedpm> :: iterator i = m_Pairedpms.begin( ); i != m_Pairedpms.end( ); ++i )
                m_GHost->m_Callables.push_back( i->second );
 
        for( vector<PairedGSCheck> :: iterator i = m_PairedGSChecks.begin( ); i != m_PairedGSChecks.end( ); ++i )
                m_GHost->m_Callables.push_back( i->second );
 
        for( vector<PairedRankCheck> :: iterator i = m_PairedRankChecks.begin( ); i != m_PairedRankChecks.end( ); ++i )
                m_GHost->m_Callables.push_back( i->second );
 
        for( vector<PairedStreakCheck> :: iterator i = m_PairedStreakChecks.begin( ); i != m_PairedStreakChecks.end( ); ++i )
                m_GHost->m_Callables.push_back( i->second );
 
        for( vector<PairedINCheck> :: iterator i = m_PairedINChecks.begin( ); i != m_PairedINChecks.end( ); ++i )
                m_GHost->m_Callables.push_back( i->second );
 
        for( vector<PairedSCheck> :: iterator i = m_PairedSChecks.begin( ); i != m_PairedSChecks.end( ); ++i )
                m_GHost->m_Callables.push_back( i->second );
 
        for( vector<PairedPWCheck> :: iterator i = m_PairedPWChecks.begin( ); i != m_PairedPWChecks.end( ); ++i )
                m_GHost->m_Callables.push_back( i->second );
 
        for( vector<PairedPassCheck> :: iterator i = m_PairedPassChecks.begin( ); i != m_PairedPassChecks.end( ); ++i )
                m_GHost->m_Callables.push_back( i->second );
 
        for( vector<PairedSS> :: iterator i = m_PairedSSs.begin( ); i != m_PairedSSs.end( ); ++i )
                m_GHost->m_Callables.push_back( i->second );
 
        for( vector<CDBBan *> :: iterator i = m_DBBans.begin( ); i != m_DBBans.end( ); ++i )
                delete *i;
 
        delete m_DBGame;
 
        for( vector<CDBGamePlayer *> :: iterator i = m_DBGamePlayers.begin( ); i != m_DBGamePlayers.end( ); ++i )
                delete *i;
 
        delete m_Stats;
 
        // it's a "bad thing" if m_CallableGameAdd is non NULL here
        // it means the game is being deleted after m_CallableGameAdd was created (the first step to saving the game data) but before the associated thread terminated
        // rather than failing horribly we choose to allow the thread to complete in the orphaned callables list but step 2 will never be completed
        // so this will create a game entry in the database without any gameplayers and/or DotA stats
 
        if( m_CallableGameAdd )
        {
                CONSOLE_Print( "[GAME: " + m_GameName + "] game is being deleted before all game data was saved, game data has been lost" );
                m_GHost->m_Callables.push_back( m_CallableGameAdd );
        }
}
 
bool CGame :: Update( void *fd, void *send_fd )
{
        // update callables
        for( vector<PairedPWCheck> :: iterator i = m_PairedPWChecks.begin( ); i != m_PairedPWChecks.end( ); )
        {
                if( i->second->GetReady( ) )
                {
                        uint32_t Result = i->second->GetResult( );
                        CGamePlayer *Player = GetPlayerFromName( i->first, true );
                        if( Player )
                        {
                                if( Result == 2 ) {
                                        SendChat( Player, "This Account is password protected, please enter your password with '!pw <YOUR PASSWORD>' or you will be kicked." );
                                        Player->SetPasswordProt( true );
                                        Player->SetRegistered( true );
                                }
                                else if( Result == 1 ) {
                                        Player->SetRegistered( true );
                                }
                        }
 
                        m_GHost->m_DB->RecoverCallable( i->second );
                        delete i->second;
                        i = m_PairedPWChecks.erase( i );
                }
                else
                        ++i;
        }
 
        for( vector<PairedPassCheck> :: iterator i = m_PairedPassChecks.begin( ); i != m_PairedPassChecks.end( ); )
        {
                if( i->second->GetReady( ) )
                {
                        uint32_t Result = i->second->GetResult( );
                        CGamePlayer *Player = GetPlayerFromName( i->first, true );
                        if( Player )
                        {
                                if( Result == 1 )
                                {
                                        SendChat( Player, "Password was successfully send." );
                                        Player->SetPasswordProt( false );
                                        Player->SetSpoofed( true );
                                }
                                else if ( Result == 2 )
                                        SendChat( Player, "Error. Wrong Password" );
                                else if ( Result == 3 )
                                        SendChat( Player, "Error. You havent activated your password protection." );
                                else if ( Result == 4 )
                                        SendChat( Player, "Successfully removed your password protection." );
                                else
                                        SendAllChat( "Something is wrong there." );
                        }
 
                        m_GHost->m_DB->RecoverCallable( i->second );
                        delete i->second;
                        i = m_PairedPassChecks.erase( i );
                }
                else
                        ++i;
        }
 
        for( vector<PairedBanCheck> :: iterator i = m_PairedBanChecks.begin( ); i != m_PairedBanChecks.end( ); )
        {
                if( i->second->GetReady( ) )
                {
                        CDBBan *Ban = i->second->GetResult( );
/*
                        if( Ban )
                                SendAllChat( m_GHost->m_Language->UserWasBannedOnByBecause( i->second->GetServer( ), i->second->GetUser( ), Ban->GetDate( ), Ban->GetAdmin( ), Ban->GetReason( ), Ban->GetExpire( ), Ban->GetRemain() ) );
                        else
                                SendAllChat( m_GHost->m_Language->UserIsNotBanned( i->second->GetServer( ), i->second->GetUser( ) ) );
*/
                        m_GHost->m_DB->RecoverCallable( i->second );
                        delete i->second;
                        i = m_PairedBanChecks.erase( i );
                }
                else
                        ++i;
        }
 
        for( vector<Pairedpm> :: iterator i = m_Pairedpms.begin( ); i != m_Pairedpms.end( ); )
        {
                if( i->second->GetReady( ) )
                {
                        uint32_t Result = i->second->GetResult( );
                        CGamePlayer *Player = GetPlayerFromName( i->first, true );
                        if( Player )
                        {
                                if( Result == -1 )
                                        SendChat( Player, "The Message has successfully stored." );
                                else if( Result == 2 )
                                        SendChat( Player, "Welcome [" + Player->GetName( ) + "] You have [" + UTIL_ToString( Result ) + "] Message(s). Check it out with the command: '!inbox'." );
                        }
 
                        m_GHost->m_DB->RecoverCallable( i->second );
                        delete i->second;
                        i = m_Pairedpms.erase( i );
                }
                else
                        ++i;
        }
 
        for( vector<PairedGSCheck> :: iterator i = m_PairedGSChecks.begin( ); i != m_PairedGSChecks.end( ); )
        {
                if( i->second->GetReady( ) )
                {
                        CDBStatsPlayerSummary *StatsPlayerSummary = i->second->GetResult( );
 
                        if( StatsPlayerSummary )
                        {
                                if( i->first.empty( ) )
                                {
                                        string Streak = UTIL_ToString( StatsPlayerSummary->GetStreak( ) );
                                        if( StatsPlayerSummary->GetStreak( ) < 0 )
                                                string Streak = "-" + UTIL_ToString( StatsPlayerSummary->GetStreak( ) );
 
                                        SendAllChat( m_GHost->m_Language->HasPlayedGamesWithThisBot( StatsPlayerSummary->GetPlayer( ),
                                                UTIL_ToString( StatsPlayerSummary->GetScore( ), 0 ),
                                                UTIL_ToString( StatsPlayerSummary->GetGames( ) ),
                                                UTIL_ToString( StatsPlayerSummary->GetWinPerc( ), 2 ),
                                                Streak ) );
                                }
                                else
                                {
                                        CGamePlayer *Player = GetPlayerFromName( i->first, true );
 
                                        if( Player )
                                        {
                                                uint32_t sn = StatsPlayerSummary->GetStreak( );
                                                string Streak = "";
                                                if( sn < 0 )
                                                        string Streak = "-" + UTIL_ToString( sn );
                                                else
                                                        string Streak = UTIL_ToString( sn );
 
                                                SendChat( Player, m_GHost->m_Language->HasPlayedGamesWithThisBot( Player->GetName( ),
                                                UTIL_ToString( StatsPlayerSummary->GetScore( ), 0 ),
                                                UTIL_ToString( StatsPlayerSummary->GetGames( ) ),
                                                UTIL_ToString( StatsPlayerSummary->GetWinPerc( ), 2 ),
                                                Streak ) );
                                        }
                                }
                        }
                        else
                        {
                                if( i->first.empty( ) )
                                        SendAllChat( m_GHost->m_Language->HasntPlayedGamesWithThisBot( i->second->GetName( ) ) );
                                else
                                {
                                        CGamePlayer *Player = GetPlayerFromName( i->first, true );
 
                                        if( Player )
                                                SendChat( Player, m_GHost->m_Language->HasntPlayedGamesWithThisBot( i->second->GetName( ) ) );
                                }
                        }
 
                        m_GHost->m_DB->RecoverCallable( i->second );
                        delete i->second;
                        i = m_PairedGSChecks.erase( i );
                }
                else
                        ++i;
        }
 
        for( vector<PairedRankCheck> :: iterator i = m_PairedRankChecks.begin( ); i != m_PairedRankChecks.end( ); )
        {
                if( i->second->GetReady( ) )
                {
                        CDBStatsPlayerSummary *StatsPlayerSummary = i->second->GetResult( );
 
                        if( StatsPlayerSummary )
                        {
                                if( i->first.empty( ) )
                                {
                                        uint32_t Level = 0;
                                        string LevelName;
                                        for( vector<CBNET *> :: iterator k = m_GHost->m_BNETs.begin( ); k != m_GHost->m_BNETs.end( ); ++k )
                                        {
                                                if( (*k)->GetServer( ) == StatsPlayerSummary->GetRealm( ) )
                                                {
                                                        Level = (*k)->IsLevel( i->second->GetName( ) );
                                                        LevelName = (*k)->GetLevelName( Level );
                                                        break;
                                                }
                                        }
 
                                        SendAllChat( "["+StatsPlayerSummary->GetPlayer( )+"] Rank: "+StatsPlayerSummary->GetRank( )+" Level: "+UTIL_ToString(Level)+" Class: "+LevelName );
                                }
                                else
                                {
                                        CGamePlayer *Player = GetPlayerFromName( i->first, true );
 
                                        if( Player )
                                        {
                                                uint32_t Level = 0;
                                                string LevelName;
                                                for( vector<CBNET *> :: iterator k = m_GHost->m_BNETs.begin( ); k != m_GHost->m_BNETs.end( ); ++k )
                                                {
                                                        if( (*k)->GetServer( ) == Player->GetSpoofedRealm( ) )
                                                        {
                                                                Level = (*k)->IsLevel( Player->GetName( ) );
                                                                LevelName = (*k)->GetLevelName( Level );
                                                                break;
                                                        }
                                                }
 
                                                SendAllChat( "["+Player->GetName( )+"] Rank: "+StatsPlayerSummary->GetRank( )+" Level: "+UTIL_ToString(Level)+" Class: "+LevelName );
                                        }
                                }
                        }
                        else
                        {
                                if( i->first.empty( ) )
                                        SendAllChat( m_GHost->m_Language->HasntPlayedGamesWithThisBot( i->second->GetName( ) ) );
                                else
                                {
                                        CGamePlayer *Player = GetPlayerFromName( i->first, true );
 
                                        if( Player )
                                                SendChat( Player, m_GHost->m_Language->HasntPlayedGamesWithThisBot( Player->GetName( ) ) );
                                }
                        }
 
                        m_GHost->m_DB->RecoverCallable( i->second );
                        delete i->second;
                        i = m_PairedRankChecks.erase( i );
                }
                else
                        ++i;
        }
 
 
        for( vector<PairedStreakCheck> :: iterator i = m_PairedStreakChecks.begin( ); i != m_PairedStreakChecks.end( ); )
        {
                if( i->second->GetReady( ) )
                {
                        CDBStatsPlayerSummary *StatsPlayerSummary = i->second->GetResult( );
 
                        if( StatsPlayerSummary )
                        {
                                if( StatsPlayerSummary->GetStreak( ) != 0 )
                                        SendAllChat( "[" + StatsPlayerSummary->GetPlayer( ) + "] Current Streak: " + UTIL_ToString( StatsPlayerSummary->GetStreak( ) ) + " | Max Streak: " + UTIL_ToString( StatsPlayerSummary->GetMaxStreak( ) ) + " | Max LosingStreak: " + UTIL_ToString( StatsPlayerSummary->GetMaxLosingStreak( ) ) );
                                else
                                        SendAllChat( "[" + StatsPlayerSummary->GetPlayer( ) + "] Current Streak: -" + UTIL_ToString( StatsPlayerSummary->GetLosingStreak( ) ) + " | Max Streak: " + UTIL_ToString( StatsPlayerSummary->GetMaxStreak( ) ) + " | Max Losing Streak: " + UTIL_ToString( StatsPlayerSummary->GetMaxLosingStreak( ) ) );
                        }
                        else
                                SendAllChat( m_GHost->m_Language->HasntPlayedGamesWithThisBot( i->second->GetName( ) ) );
 
                        m_GHost->m_DB->RecoverCallable( i->second );
                        delete i->second;
                        i = m_PairedStreakChecks.erase( i );
                }
                else
                        ++i;
        }
 
        for( vector<PairedINCheck> :: iterator i = m_PairedINChecks.begin( ); i != m_PairedINChecks.end( ); )
        {
                if( i->second->GetReady( ) )
                {
                        CDBInboxSummary *InboxSummary = i->second->GetResult( );
                        CGamePlayer *Player = GetPlayerFromName( i->first, true );
                        if( Player )
                        {
                                if( InboxSummary )
                                        SendChat( Player, "[" + InboxSummary->GetUser( ) + "] " + InboxSummary->GetMessage( ) );
                                else
                                        SendChat( Player, "Your Inbox is empty." );
                        }
 
                        m_GHost->m_DB->RecoverCallable( i->second );
                        delete i->second;
                        i = m_PairedINChecks.erase( i );
                }
                else
                        ++i;
        }
 
        for( vector<PairedSCheck> :: iterator i = m_PairedSChecks.begin( ); i != m_PairedSChecks.end( ); )
        {
                if( i->second->GetReady( ) )
                {
                        CDBStatsPlayerSummary *StatsPlayerSummary = i->second->GetResult( );
 
                        if( StatsPlayerSummary )
                        {
                                string Summary = m_GHost->m_Language->HasPlayedDotAGamesWithThisBot(    i->second->GetName( ),
                                        UTIL_ToString( StatsPlayerSummary->GetGames( ) ),
                                        UTIL_ToString( StatsPlayerSummary->GetWins( ) ),
                                        UTIL_ToString( StatsPlayerSummary->GetLosses( ) ),
                                        UTIL_ToString( StatsPlayerSummary->GetKills( ) ),
                                        UTIL_ToString( StatsPlayerSummary->GetDeaths( ) ),
                                        UTIL_ToString( StatsPlayerSummary->GetCreeps( ) ),
                                        UTIL_ToString( StatsPlayerSummary->GetDenies( ) ),
                                        UTIL_ToString( StatsPlayerSummary->GetAssists( ) ),
                                        UTIL_ToString( StatsPlayerSummary->GetNeutrals( ) ),
                                        UTIL_ToString( StatsPlayerSummary->GetTowers( ) ),
                                        UTIL_ToString( StatsPlayerSummary->GetRax( ) ),
                                        UTIL_ToString( StatsPlayerSummary->GetAvgKills( ), 2 ),
                                        UTIL_ToString( StatsPlayerSummary->GetAvgDeaths( ), 2 ),
                                        UTIL_ToString( StatsPlayerSummary->GetAvgCreeps( ), 2 ),
                                        UTIL_ToString( StatsPlayerSummary->GetAvgDenies( ), 2 ),
                                        UTIL_ToString( StatsPlayerSummary->GetAvgAssists( ), 2 ),
                                        UTIL_ToString( StatsPlayerSummary->GetAvgNeutrals( ), 2 ),
                                        UTIL_ToString( StatsPlayerSummary->GetAvgTowers( ), 2 ),
                                        UTIL_ToString( StatsPlayerSummary->GetAvgRax( ), 2 ) );
 
                                if( i->first.empty( ) )
                                        SendAllChat( Summary );
                                else
                                {
                                        CGamePlayer *Player = GetPlayerFromName( i->first, true );
 
                                        if( Player )
                                                SendChat( Player, Summary );
                                }
                        }
                        else
                        {
                                if( i->first.empty( ) )
                                        SendAllChat( m_GHost->m_Language->HasntPlayedDotAGamesWithThisBot( i->second->GetName( ) ) );
                                else
                                {
                                        CGamePlayer *Player = GetPlayerFromName( i->first, true );
 
                                        if( Player )
                                                SendChat( Player, m_GHost->m_Language->HasntPlayedDotAGamesWithThisBot( i->second->GetName( ) ) );
                                }
                        }
 
                        m_GHost->m_DB->RecoverCallable( i->second );
                        delete i->second;
                        i = m_PairedSChecks.erase( i );
                }
                else
                        ++i;
        }
 
        for( vector<PairedSS> :: iterator i = m_PairedSSs.begin( ); i != m_PairedSSs.end( ); )
        {
                if( i->second->GetReady( ) )
                {
                        string Result = i->second->GetResult( );
                        CGamePlayer *Player = GetPlayerFromName( i->second->GetUser( ), true );
                        if( i->second->GetType( ) == "betcheck" || i->second->GetType( ) == "bet" )
                        {
                                if( i->second->GetType( ) == "betcheck" )
                                        SendAllChat( "[" + i->second->GetUser( ) + "] Current Points: " + Result );
 
                                else if( i->second->GetType( ) == "bet" )
                                {
                                        if( Result == "already bet" )
                                                SendChat( Player, "You already bet" );
                                        else if( Result == "successfully bet" )
                                                SendAllChat( "User [" + i->second->GetUser( ) + "] bet [" + UTIL_ToString( i->second->GetOne( ) ) + "] to win this game." );
                                        else if ( Result != "failed" )
                                                SendChat( Player, "You shouldn't bet more points you got, you got currently [" + Result + " ] points" );
                                        else
                                                CONSOLE_Print( "Betsystem have an issue here" );
                                }
                                else if( Result == "not listed" )
                                        SendChat( Player, "You need to play at least one game to bet here" );
                                else
                                        CONSOLE_Print( "Betsystem has an issue here" );
                        }
                        m_GHost->m_DB->RecoverCallable( i->second );
                        delete i->second;
                        i = m_PairedSSs.erase( i );
                }
                else
                        ++i;
        }
 
        if( m_ForfeitTime != 0 && GetTime( ) - m_ForfeitTime >= 5 )
        {
                // kick everyone on forfeit team
 
                for( vector<CGamePlayer *> :: iterator i = m_Players.begin( ); i != m_Players.end( ); i++)
                {
                        if( *i && !(*i)->GetLeftMessageSent( ) )
                        {
                                char sid = GetSIDFromPID( (*i)->GetPID( ) );
 
                                if( sid != 255 && m_Slots[sid].GetTeam( ) == m_ForfeitTeam )
                                {
                                        (*i)->SetDeleteMe( true );
                                        (*i)->SetLeftReason( "forfeited" );
                                        (*i)->SetLeftCode( PLAYERLEAVE_LOST );
                                }
                        }
                }
 
                string ForfeitTeamString = "Sentinel";
                if( m_ForfeitTeam == 1 ) ForfeitTeamString = "Scourge";
 
                SendAllChat( "The [" + ForfeitTeamString + "] players have been removed from the game." );
                SendAllChat( "Please wait five seconds before leaving so that stats can be properly saved." );
 
                m_ForfeitTime = 0;
                m_GameOverTime = GetTime( );
        }
 
        return CBaseGame :: Update( fd, send_fd );
}
 
void CGame :: EventPlayerDeleted( CGamePlayer *player )
{
 
        CBaseGame :: EventPlayerDeleted( player );
 
        // record everything we need to know about the player for storing in the database later
        // since we haven't stored the game yet (it's not over yet!) we can't link the gameplayer to the game
        // see the destructor for where these CDBGamePlayers are stored in the database
        // we could have inserted an incomplete record on creation and updated it later but this makes for a cleaner interface
 
        if( m_GameLoading || m_GameLoaded )
        {
                // todotodo: since we store players that crash during loading it's possible that the stats classes could have no information on them
                // that could result in a DBGamePlayer without a corresponding DBDotAPlayer - just be aware of the possibility
 
                unsigned char SID = GetSIDFromPID( player->GetPID( ) );
                unsigned char Team = 255;
                unsigned char Colour = 255;
 
                if( SID < m_Slots.size( ) )
                {
                        Team = m_Slots[SID].GetTeam( );
                        Colour = m_Slots[SID].GetColour( );
                }
 
                m_DBGamePlayers.push_back( new CDBGamePlayer( 0, 0, player->GetName( ), player->GetExternalIPString( ), player->GetSpoofed( ) ? 1 : 0, player->GetSpoofedRealm( ), player->GetReserved( ) ? 1 : 0, player->GetFinishedLoading( ) ? player->GetFinishedLoadingTicks( ) - m_StartedLoadingTicks : 0, m_GameTicks / 1000, player->GetLeftReason( ), Team, Colour ) );
 
                // also keep track of the last player to leave for the !banlast command
 
                for( vector<CDBBan *> :: iterator i = m_DBBans.begin( ); i != m_DBBans.end( ); ++i )
                {
                        if( (*i)->GetName( ) == player->GetName( ) )
                                m_DBBanLast = *i;
                }
 
                // if this was early leave, suggest to draw the game
                if( m_GameTicks < 1000 * 60 )
                        SendAllChat( "Use !draw to vote to draw the game." );
 
                if( Team != 12 && m_GameOverTime == 0 && m_ForfeitTime == 0 )
                {
                        // check if everyone on leaver's team left but other team has more than two players
                        char sid, team;
                        uint32_t CountAlly = 0;
                        uint32_t CountEnemy = 0;
 
                        for( vector<CGamePlayer *> :: iterator i = m_Players.begin( ); i != m_Players.end( ); i++)
                        {
                                if( *i && !(*i)->GetLeftMessageSent( ) )
                                {
                                        char sid = GetSIDFromPID( (*i)->GetPID( ) );
                                        if( sid != 255 )
                                        {
                                                char team = m_Slots[sid].GetTeam( );
                                                if( team == Team )
                                                        CountAlly++;
                                                else
                                                        CountEnemy++;
                                        }
                                }
                        }
 
                        // autoend, check gameplayer spread
                        uint32_t spread = CountAlly > CountEnemy ? CountAlly - CountEnemy : CountEnemy - CountAlly;
 
                        if( spread <= 1 )
                        {
                                m_AutoBans.push_back( player->GetName( ) );
                                SendAllChat( "User ["+player->GetName( ) +"] will be autobanned at the end of the game, if he/seh didn't left within the last 5 minutes." );
                        }
 
                        if( m_GHost->m_MaxAllowedSpread >= 3 && m_Stats )
                        {
                                SendAllChat( "[AUTO-END] The spread between the two teams is already ["+UTIL_ToString(spread)+"]" );
                                m_Stats->SetWinner( ( team + 1 ) % 2 );
                                string WinTeam = ( ( ( team + 1 ) % 2 )  == 1 ? "Scourge" : "Sentinel" );
                                SendAllChat( "[AUTO-END] The game will end in fifty seconds. The winner is set to ["+ WinTeam +"]" );
                                SendAllChat( "[AUTO-END] Please stay until the end to save all stats correctly." );
                                m_GameOverTime = GetTime( );
                        }
 
                        if( CountAlly+CountEnemy <= m_GHost->m_MinPlayerAutoEnd && m_Stats )
                        {
                                //Weired missing autoend //recheck later
                                string Winner = ( team + 1 ) % 2 == 1 ? "Sentinel" : "Scourge";
                                SendAllChat("[AUTO-END] Too few players ingame, this game will end in fifteen seconds." );
                                SendAllChat("[AUTO-END] Winning team was set to ["+ Winner +"]" );
                                m_Stats->SetWinner( ( team + 1 ) % 2 );
                                //m_Stats->LockStats( );
                                //m_SoftGameOver = true;
                                m_GameOverTime = GetTime( );
                        }
 
                        if( CountAlly == 0 && CountEnemy >= 2 )
                        {
                                // if less than one minute has elapsed, draw the game
                                // this may be abused for mode voting and such, but hopefully not (and that's what bans are for)
                                if( m_GameTicks < 1000 * 180 )
                                {
                                        SendAllChat( "[AUTO-END] Only one team is remaining, this game will end in fifteen seconds and be recorded as a draw." );
                                        m_GameOverTime = GetTime( );
                                }
 
                                // otherwise, if more than fifteen minutes have elapsed, give the other team the win
                                else if( m_GameTicks > 1000 * 180 && m_Stats )
                                {
                                        SendAllChat( "[AUTO-END] The other team has left, this game will be recorded as your win. You may leave at any time." );
                                        m_Stats->SetWinner( ( team + 1 ) % 2 );
                                        m_Stats->LockStats( );
                                        m_SoftGameOver = true;
                                        m_GameOverTime = GetTime( );
                                }
                        }
                }
 
                // if stats and not solo, and at least two leavers in first four minutes, then draw the game
                if( !m_SoftGameOver && m_Stats && m_GameOverTime == 0 && Team != 12 && m_GameTicks < 1000 * 60 * 7 && m_GHost->m_EarlyEnd )
                {
                        // check how many leavers, by starting from start players and subtracting each non-leaver player
                        uint32_t m_NumLeavers = m_StartPlayers;
 
                        for( vector<CGamePlayer *> :: iterator i = m_Players.begin( ); i != m_Players.end( ); i++)
                        {
                                if( *i && !(*i)->GetLeftMessageSent( ) && *i != player )
                                        m_NumLeavers--;
                        }
 
                        if( m_NumLeavers >= 2 )
                        {
                                SendAllChat( "[AUTO-END] Two players have left in the first few minutes." );
                                SendAllChat( "[AUTO-END] This game has been marked as a draw. You may leave at any time." );
                                SendAllChat( "[AUTO-END] Please stay till the end to avoid any false bans!" );
 
                                // make sure leavers will get banned
                                m_GameOverTime = GetTime( );
                                m_EarlyDraw = true;
                                m_SoftGameOver = true;
                                m_Stats->LockStats( );
                        }
                }
        }
}
 
bool CGame :: EventPlayerAction( CGamePlayer *player, CIncomingAction *action )
{
        bool success = CBaseGame :: EventPlayerAction( player, action );
 
        // give the stats class a chance to process the action
 
        if( success && m_Stats && m_Stats->ProcessAction( action ) && m_GameOverTime == 0 )
        {
                CONSOLE_Print( "[GAME: " + m_GameName + "] gameover timer started" );
                SendEndMessage( );
                m_GameOverTime = GetTime( );
        }
        return success;
}
 
bool CGame :: EventPlayerBotCommand( CGamePlayer *player, string command, string payload )
{
bool HideCommand = CBaseGame :: EventPlayerBotCommand( player, command, payload );
 
// todotodo: don't be lazy
 
string User = player->GetName( );
string Command = command;
string Payload = payload;
 
uint32_t Level = 0;
string LevelName;
for( vector<CBNET *> :: iterator i = m_GHost->m_BNETs.begin( ); i != m_GHost->m_BNETs.end( ); ++i )
        {
                if( (*i)->GetServer( ) == player->GetSpoofedRealm( ) )
                {
                        Level = (*i)->IsLevel( player->GetName( ) );
                        LevelName = (*i)->GetLevelName( Level );
                        break;
                }
        }
        if( m_GHost->m_GarenaHosting )
        {
                string name = player->GetName();
                transform( name.begin( ), name.end( ), name.begin( ), (int(*)(int))tolower );
                for( vector<string> :: iterator i = m_GarenaPermissions.begin( ); i != m_GarenaPermissions.end( ); ++i )
                {
                string username;
                string lev;
                stringstream SS;
                SS << *i;
                SS >> username;
                SS >> lev;
         
                if( username == name )
                {
                                uint32_t level = UTIL_ToUInt32(lev);
                                Level = level;
                                if( level == 0 )
                                LevelName = "Level 1 User";
                                else if( level == 1 )
                                LevelName = "Level 2 User";
                                else if( level == 2 )
                                LevelName = "Level 3 User";
                                else if( level == 3 )
                                LevelName = "Safelisted User";
                                else if( level == 4 )
                                LevelName = "Devoted User";
                                else if( level == 5 )
                                LevelName = "Bot Moderator";
                                else if( level == 6 )
                                LevelName = "Full Bot Moderator";
                                else if( level == 7 )
                                LevelName = "Global Bot Moderator";
                                else if( level == 8 )
                                LevelName = "Owner";
                                else if( level == 9 )
                                LevelName = "Admin";
                                else if( level == 10 )
                                LevelName = "Root Admin";
                }
        }
}
        if( player->GetSpoofed( ) && Level >= 5 )
        {
                CONSOLE_Print( "[GAME: " + m_GameName + "] "+ LevelName +" [" + User + "] sent command [" + Command + "] with payload [" + Payload + "]" );
 
                if( ( m_Locked && Level > 8 ) || !m_Locked )
                {
                        //save admin log
                        m_AdminLog.push_back( User + " gl" + "\t" + UTIL_ToString( Level ) + "\t" + Command + "\t" + Payload );
 
                                //
                                // !ONLY
                                //
                                if( ( Command == "only" || Command == "unallow" || Command == "disallow" || Command == "deniecountry" ) && !m_GameLoading && !m_GameLoaded )
                                {
                                        if ( Payload.empty( ) || Payload == "0" || Payload == "clear" )
                                        {
                                                if( Command == "only" )
                                                {
                                                        SendAllChat( "Disabled allowed country check." );
                                                        m_LimitCountries = false;
                                                }
                                                else if( Command == "unallow" || Command == "disallow" || Command == "deniecountry" )
                                                {
                                                        SendAllChat( "Disabled unallowed country check." );
                                                        m_DenieCountries = false;
                                                }
                                                m_LimitedCountries.clear();
                                        }
                                        else if( m_DenieCountries && Command == "only" )
                                        {
                                                SendChat( player, "Currently there countries denied, please clear the deny list before using only." );
                                                return HideCommand;
                                        }
                                        else if( m_LimitCountries && Command == "unallow" || Command == "disallow" || Command == "deniecountry" )
                                        {
                                                SendChat( player, "Currently there limited countries allowed, please clear the only list before using deniecountries." );
                                                return HideCommand;
                                        }
                                        else
                                        {
                                                if( Command == "only" )
                                                        m_LimitCountries = true;
                                                else
                                                        m_DenieCountries = true;
 
                                                transform( Payload.begin( ), Payload.end( ), Payload.begin( ), (int(*)(int))toupper );
                                                m_LimitedCountries.push_back( Payload );
                                                string AllLimitedCountries;
                                                for( vector<string> :: iterator i = m_LimitedCountries.begin( ); i != m_LimitedCountries.end( ); i++ )
                                                {
                                                        if( AllLimitedCountries.empty() )
                                                                AllLimitedCountries = *i;
                                                        else
                                                                AllLimitedCountries = AllLimitedCountries + ", " + *i;
                                                }
 
                                                SendAllChat( "Country check enabled, " + ( Command = "only" ? "allowed countries: " : "denied countries: " ) + AllLimitedCountries );
 
                                                for( vector<CGamePlayer *> :: iterator i = m_Players.begin( ); i != m_Players.end( ); i++ )
                                                {
                                                        string CC = (*i)->GetCLetter( );
                                                        transform( CC.begin( ), CC.end( ), CC.begin( ), (int(*)(int))toupper );
                                                        bool isReserved;
                                                        for( vector<CBNET *> :: iterator j = m_GHost->m_BNETs.begin( ); j != m_GHost->m_BNETs.end( ); j++ )
                                                        {
                                                                if( (*j)->IsLevel( (*i)->GetName( ) ) != 0 )
                                                                {
                                                                        SendAllChat("Player: " + (*i)->GetName( ) + "("+ CC +") is " + (isReserved?"":"not ") + "a " + (*j)->GetLevelName( (*j)->IsLevel( (*i)->GetName( ) ) ) + "." );
                                                                        isReserved = true;
                                                                        break;
                                                                }
                                                        }
                                                        bool unallowedcountry = false;
                                                        for( vector<string> :: iterator k = m_LimitedCountries.begin( ); k != m_LimitedCountries.end( ); k++ )
                                                        {
                                                                if( *k == CC && m_DenieCountries )
                                                                        unallowedcountry = true;
                                                                if( *k != CC && m_LimitCountries )
                                                                        unallowedcountry = true;
                                                        }
 
                                                        if ( !isReserved && (*i)->GetName( ) != User && unallowedcountry )
                                                        {
                                                                SendAllChat( "Kicked user " + (*i)->GetName( ) + " for having an unallowed country." );
                                                                (*i)->SetDeleteMe( true );
                                                                (*i)->SetLeftReason( "was autokicked by having an unallowed country.");
                                                                (*i)->SetLeftCode( PLAYERLEAVE_LOBBY );
                                                                OpenSlot( GetSIDFromPID( (*i)->GetPID( ) ), false );
                                                        }
                                                }
                                        }
                                }
 
                                //
                                // !NOGARENA
                                //
                                else if( Command == "nogarena" && !Payload.empty() && !m_GameLoading && !m_GameLoaded )
                                {
                                        if( Payload == "on" )
                                        {
                                                m_GameNoGarena = true;
                                                SendAllChat( "No Garena option enabled for this game. Kicking all Garena Users" );
                                                for( vector<CGamePlayer *> :: iterator i = m_Players.begin( ); i != m_Players.end( ); i++ )
                                                {
                                                        bool isReserved = false;
                                                        for( vector<CBNET *> :: iterator j = m_GHost->m_BNETs.begin( ); j != m_GHost->m_BNETs.end( ); j++ )
                                                        {
                                                                if( (*j)->IsLevel( (*i)->GetName( ) ) != 0 )
                                                                {
                                                                        SendAllChat("Player: " + (*i)->GetName( ) + "("+ (*i)->GetSpoofedRealm( ) +") is " + (isReserved?"":"not ") + "a " + (*j)->GetLevelName( (*j)->IsLevel( (*i)->GetName( ) ) ) + "." );
                                                                        isReserved = true;
                                                                        break;
                                                                }
                                                        }
                                                        if( !isReserved && (*i)->GetSpoofedRealm( ) == "garena" )
                                                        {
                                                                SendAllChat( "Kicked user " + (*i)->GetName( ) + " for being a garena user." );
                                                                (*i)->SetDeleteMe( true );
                                                                (*i)->SetLeftReason( "was autokicked by having an unallowed realm.");
                                                                (*i)->SetLeftCode( PLAYERLEAVE_LOBBY );
                                                                OpenSlot( GetSIDFromPID( (*i)->GetPID( ) ), false );
                                                        }
                                                }
                                        }
                                        else if( Payload == "off" )
                                                m_GameNoGarena = false;
                                        else
                                                SendChat( player, "Error please use on/off as config settings" );
                                }
 
                                //
                                // !DENY
                                //
                                else if( Command == "deny" && !Payload.empty() && !m_GameLoading && !m_GameLoaded )
                                {
                                        CGamePlayer *LastMatch = NULL;
                                        uint32_t Matches = GetPlayerFromNamePartial( Payload, &LastMatch );
                                        if( Matches == 0 )
                                        {
                                                m_Denied.push_back( Payload + "   " + UTIL_ToString( GetTime() ) );
                                                SendAllChat( "Denied User [" + Payload + "] for this game lobby" );
                                        }
                                        else if( Matches == 1 )
                                        {
                                                m_Denied.push_back( LastMatch->GetName() + " " + LastMatch->GetExternalIPString( ) + " 0" );
                                                SendAllChat( "Denied User [" + LastMatch->GetName( ) + "] for this game lobby" );
                                                LastMatch->SetDeleteMe( true );
                                                LastMatch->SetLeftReason( "got denied for this lobby" );
                                                LastMatch->SetLeftCode( PLAYERLEAVE_LOBBY );
                                                OpenSlot( GetSIDFromPID( LastMatch->GetPID( ) ), false );
                                                m_Balanced = false;
                                        }
                                        else
                                                SendChat( player, "Error. Found multiply matches for the name: " + Payload );
                                }
 
                //
                // !CheckPP
                //
                else if( Command == "pp" || Command == "checkpp" )
                {
                    string StatsUser = User;
                    if( !Payload.empty() )
                            StatsUser = Payload;
 
                     m_Pairedpenps.push_back( Pairedpenp( string(), m_GHost->m_DB->Threadedpenp( StatsUser, "", "", 0, "check" ) ) );
                }
 
                                //
                                // !SIMULATECHAT
                                //
                                else if(Command=="simulatechat" && !Payload.empty() )
                                {
                                        string suser;
                                        string message;
                                        stringstream SS;
                                        SS<<Payload;
                                        SS>>suser;
                                        if(SS.fail()||suser.empty())
                                                SendChat(player,"Error, wrong input, use '!simulatechat user message'");
                                        else if(suser.size()<3)
                                                SendChat(player,"Error, the name is to short, please add a valied name");
                                        else
                                        {
                                                SS>>message;
                                                if(!SS.eof())
                                                {
                                                        getline(SS,message);
                                                        string :: size_type Start=message.find_first_not_of(" ");
                                                        if(Start!=string :: npos)
                                                                message=message.substr(Start);
                                                }
                                                if(message.length()>100)
                                                        SendChat(player,"Error, this message is to long, may choose a more shorten message.");
                                                else if(message.empty())
                                                        SendChat(player,"Error, there is no message set.");
                                                else
                                                {
                                                        CGamePlayer *LastMatch = NULL;
                                                        uint32_t Matches=GetPlayerFromNamePartial(suser,&LastMatch);
                                                        if(Matches==0)
                                                                SendChat(player,"Error. Found no match on the playername");
                                                        else if(Matches==1)
                                                        {
                                                                SendChat( player,"Successfully sent a simulated message");
                                                                SendAllChat((unsigned char)LastMatch->GetPID(),message);
                                                        }
                                                        else if(Matches>1)
                                                                SendChat(player,"Error. Found more than one match for this playername.");
                                                }
                                        }
                                        return true;
                                }
 
                //
                // !PPADD !PUNISH
                //
                else if(  Command == "ppadd" || Command == "punish" )
                {
                        string Victim;
                        string Amount;
                        string Reason;
                        stringstream SS;
                        SS << Payload;
                        SS >> Victim;
 
                        if( SS.fail( ) || Victim.empty() )
                                CONSOLE_Print( "[PP] bad input #1 to !TEMPBAN command" );
                        else if( Victim.size() < 3 )
                                SendChat( player, "Error. The name is too short, please add a valied name" );
                        else
                        {
                                SS >> Amount;
 
                                if( SS.fail( ) || Amount == "0" )
                                        CONSOLE_Print( "[PP] bad input #2 to !TEMPBAN command" );
                                else if( ( UTIL_ToUInt32( Amount ) > 3 && Level < 8 ) || UTIL_ToUInt32( Amount ) > 10 && Level <= 10 )
                                        SendChat( player, "You shouldn't add more than 3 penality points" );
                                else
                                {
                                        SS >> Reason;
 
                                        if( !SS.eof( ) )
                                        {
                                                getline( SS, Reason );
                                                string :: size_type Start = Reason.find_first_not_of( " " );
 
                                                if( Start != string :: npos )
                                                        Reason = Reason.substr( Start );
                                        }
                                        if( !Reason.empty() )
                                        {
                                                CGamePlayer *LastMatch = NULL;
                                                uint32_t Matches = GetPlayerFromNamePartial( Victim, &LastMatch );
                                                if( Matches == 0 )
                                                SendChat( player, "Error. Found no match on the playername" );
                                        else if( Matches == 1 )
                                                    m_Pairedpenps.push_back( Pairedpenp( string(), m_GHost->m_DB->Threadedpenp( LastMatch->GetName( ), Reason, User, UTIL_ToUInt32( Amount ), "add" ) ) );
                                        else if( Matches > 1 )
                                                        SendChat( player, "Error. Found more than one match for this playername." );
                                        }
                                        else
                                                SendChat( player, "Error. Please state a reason to punish someone" );
                                }
                        }
                }
 
                                //
                                //setcookies
                                //
                                else if( Command == "setcookies" && Level >= 9 )
                                {
                                        if( Payload.empty( ) )
                                        {
                                                SendAllChat( "Player "+player->GetName()+" refilled his cookie jar." );
                                                player->SetCookie( 3 );
                                        }
                                        else
                                        {
                                                CGamePlayer *LastMatch = NULL;
                                                uint32_t Matches = GetPlayerFromNamePartial( Payload, &LastMatch );
 
                                                if( Matches == 0 )
                                                        SendChat( player, "Error. Found no match on the playername" );
                                                else if( Matches == 1 )
                                                {
                                                        LastMatch->SetCookie( 3 );
                                                        SendAllChat( "Player "+player->GetName()+" refilled "+LastMatch->GetName()+"'s cookie jar." );
                                                }
                                                else if( Matches > 1 )
                                                        SendChat( player, "Error. Found more than one match for this playername." );
                                        }
                                }
 
                        /*****************
                        * ADMIN COMMANDS *
                        ******************/
                        //
                        // !ABORT (abort countdown)
                        // !A
                        //
 
                        // we use "!a" as an alias for abort because you don't have much time to abort the countdown so it's useful for the abort command to be easy to type
                        else if( ( Command == "abort" || Command == "a" ) && m_CountDownStarted && !m_GameLoading && !m_GameLoaded && Level >= 7 )
                        {
                                SendAllChat( m_GHost->m_Language->CountDownAborted( ) );
                                m_CountDownStarted = false;
                        }
 
                        //
                        // !AUTOBALANCE
                        // !ABC
                        //
                        else if ( ( Command == "autobalance" || Command == "ab" || Command == "abc" ) && Level == 10 && Payload.empty() )
                        {
                                OHFixedBalance( );
                        }
 
                        //
                        // !ADDBAN
                        // !BAN
                        //
                        else if( ( Command == "addban" || Command == "ban" || Command == "b" ) && !Payload.empty( ) && !m_GHost->m_BNETs.empty( ) )
                        {
                                if( Level >= 7 )
                                {
                                        // extract the victim and the reason
                                        // e.g. "Varlock leaver after dying" -> victim: "Varlock", reason: "leaver after dying"
 
                                        string Victim;
                                        string Reason;
                                        stringstream SS;
                                        SS << Payload;
                                        SS >> Victim;
 
                                        if( !SS.eof( ) )
                                        {
                                                getline( SS, Reason );
                                                string :: size_type Start = Reason.find_first_not_of( " " );
                                                if( Start != string :: npos )
                                                        Reason = Reason.substr( Start );
                                        }
 
                                        if( m_GameLoaded )
                                        {
                                                string VictimLower = Victim;
                                                transform( VictimLower.begin( ), VictimLower.end( ), VictimLower.begin( ), (int(*)(int))tolower );
                                                uint32_t Matches = 0;
                                                CDBBan *LastMatch = NULL;
 
                                                // try to match each player with the passed string (e.g. "Varlock" would be matched with "lock")
                                                // we use the m_DBBans vector for this in case the player already left and thus isn't in the m_Players vector anymore
 
                                                for( vector<CDBBan *> :: iterator i = m_DBBans.begin( ); i != m_DBBans.end( ); ++i )
                                                {
                                                        string TestName = (*i)->GetName( );
                                                        transform( TestName.begin( ), TestName.end( ), TestName.begin( ), (int(*)(int))tolower );
 
                                                        if( TestName.find( VictimLower ) != string :: npos )
                                                        {
                                                                Matches++;
                                                                LastMatch = *i;
 
                                                                // if the name matches exactly stop any further matching
 
                                                                if( TestName == VictimLower )
                                                                {
                                                                        Matches = 1;
                                                                        break;
                                                                }
                                                        }
                                                }
 
                                                if( Matches == 0 )
                                                        SendAllChat( m_GHost->m_Language->UnableToBanNoMatchesFound( Victim ) );
                                                else if( Matches == 1 )
                                                        m_PairedBanAdds.push_back( PairedBanAdd( User, m_GHost->m_DB->ThreadedBanAdd( LastMatch->GetServer( ), LastMatch->GetName( ), LastMatch->GetIP( ), m_GameName, User, Reason, 0, "" ) ) );
                                                else
                                                        SendAllChat( m_GHost->m_Language->UnableToBanFoundMoreThanOneMatch( Victim ) );
                                        }
                                        else
                                        {
                                                CGamePlayer *LastMatch = NULL;
                                                uint32_t Matches = GetPlayerFromNamePartial( Victim, &LastMatch );
 
                                                if( Matches == 0 )
                                                        SendAllChat( m_GHost->m_Language->UnableToBanNoMatchesFound( Victim ) );
                                                else if( Matches == 1 )
                                                        m_PairedBanAdds.push_back( PairedBanAdd( User, m_GHost->m_DB->ThreadedBanAdd( LastMatch->GetJoinedRealm( ), LastMatch->GetName( ), LastMatch->GetExternalIPString( ), m_GameName, User, Reason, 0, LastMatch->GetCLetter( ) ) ) );
                                                else
                                                        SendAllChat( m_GHost->m_Language->UnableToBanFoundMoreThanOneMatch( Victim ) );
                                        }
                                }
                                else
                                        SendChat( player, "You dont have access to run this command, please use '!tban'" );
                        }
 
           //
           // !TEMPBAN
           // !TBAN
           //
 
           if( ( Command == "tempban" || Command == "tban" || Command == "tb" ) && !Payload.empty( ) && !m_GHost->m_BNETs.empty( ) )
           {
                   // extract the victim and the reason
                   // e.g. "Varlock leaver after dying" -> victim: "Varlock", reason: "leaver after dying"
 
                   string Victim;
                   string Reason;
 
                   uint32_t Amount;
                   uint32_t BanTime;
                   string Suffix;
 
                   stringstream SS;
                   SS << Payload;
                   SS >> Victim;
 
                   if( SS.fail( ) || Victim.empty() )
                           CONSOLE_Print( "[TEMPBAN] bad input #1 to !TEMPBAN command" );
                   else if( Victim.size() < 3 )
                           SendChat( player, "Error. The name is too short, please add a valied name" );
                   else
                   {
                           SS >> Amount;
 
                           if( SS.fail( ) || Amount == 0 )
                                   CONSOLE_Print( "[TEMPBAN] bad input #2 to !TEMPBAN command" );
                           else
                           {
                                   SS >> Suffix;
 
                                   if (SS.fail() || Suffix.empty())
                                           CONSOLE_Print( "[TEMPBAN] bad input #3 to autohost command" );
                                   else
                                   {
                                           uint32_t BanTime = 0;
 
                                           // handle suffix
                                           // valid suffix is: hour, h, week, w, day, d, month, m
                                           bool ValidSuffix = false;
                                           transform( Suffix.begin( ), Suffix.end( ), Suffix.begin( ), (int(*)(int))tolower );
 
                                           if (Suffix == "hour" || Suffix == "hours" || Suffix == "h")
                                           {
                                                   BanTime = Amount * 3600;
                                                   ValidSuffix = true;
                                           }
                                           else if (Suffix == "day" || Suffix == "days" || Suffix == "d")
                                           {
                                                   BanTime = Amount * 86400;
                                                   ValidSuffix = true;
                                           }
                                           else if (Suffix == "week" || Suffix == "weeks" || Suffix == "w")
                                           {
                                                   BanTime = Amount * 604800;
                                                   ValidSuffix = true;
                                           }
                                           else if (Suffix == "month" || Suffix == "months" || Suffix == "m")
                                           {
                                                   BanTime = Amount * 2419200;
                                                   ValidSuffix = true;
                                           }
 
                                           if (ValidSuffix)
                                           {
 
                                                   if (!SS.eof())
                                                   {
                                                           getline( SS, Reason );
                                                           string :: size_type Start = Reason.find_first_not_of( " " );
 
                                                           if( Start != string :: npos )
                                                                   Reason = Reason.substr( Start );
                                                   }
 
                                                   //SendAllChat("Temporary ban: " + Victim + " for " + UTIL_ToString(Amount) + " " + Suffix + " with reason: " + Reason);
 
                                                   if( m_GameLoaded )
                                                   {
                                                           string VictimLower = Victim;
                                                           transform( VictimLower.begin( ), VictimLower.end( ), VictimLower.begin( ), (int(*)(int))tolower );
                                                           uint32_t Matches = 0;
                                                           CDBBan *LastMatch = NULL;
                                                           // try to match each player with the passed string (e.g. "Varlock" would be matched with "lock")
                                                           // we use the m_DBBans vector for this in case the player already left and thus isn't in the m_Players vector anymore
 
                                                           for( vector<CDBBan *> :: iterator i = m_DBBans.begin( ); i != m_DBBans.end( ); i++ )
                                                           {
                                                                   string TestName = (*i)->GetName( );
                                                                   transform( TestName.begin( ), TestName.end( ), TestName.begin( ), (int(*)(int))tolower );
 
                                                                   if( TestName.find( VictimLower ) != string :: npos )
                                                                   {
                                                                           Matches++;
                                                                           LastMatch = *i;
                                                                   }
                                                           }
 
                                                           if( Matches == 0 )
                                                                   SendAllChat( m_GHost->m_Language->UnableToBanNoMatchesFound( Victim ) );
                                                           else if( Matches == 1 )
                                                {
                                                                uint32_t VictimLevel = 0;
                                                                string VictimLevelName;
                                                                for( vector<CBNET *> :: iterator i = m_GHost->m_BNETs.begin( ); i != m_GHost->m_BNETs.end( ); ++i )
                                                                {
                                                                        if( (*i)->GetServer( ) == LastMatch->GetServer( ) )
                                                                        {
                                                                                        VictimLevel = (*i)->IsLevel( LastMatch->GetName( ) );
                                                                                VictimLevelName = (*i)->GetLevelName( VictimLevel );
                                                                                break;
                                                                        }
                                                                }
                                                                if( Level >= 7 || ( Level == 5 ||  Level == 6 ) && ( ( Suffix == "hour" || Suffix == "hours" || Suffix == "h" ) || ( ( Suffix == "days" || Suffix == "d" || Suffix == "day" ) && Amount <= 5 ) ) )
                                                                        m_PairedBanAdds.push_back( PairedBanAdd( User, m_GHost->m_DB->ThreadedBanAdd( LastMatch->GetServer( ), LastMatch->GetName( ), LastMatch->GetIP( ), m_GameName, User, Reason, BanTime, "" ) ) );
                                                                else
                                                                    SendChat( player, "You have no permission to ban this player." );
                                                }
                                                           else
                                                                   SendAllChat( m_GHost->m_Language->UnableToBanFoundMoreThanOneMatch( Victim ) );
                                                   }
                                                   else
                                                   {
                                                           CGamePlayer *LastMatch = NULL;
                                                           uint32_t Matches = GetPlayerFromNamePartial( Victim, &LastMatch );
 
                                                           if( Matches == 0 )
                                                                   SendAllChat( m_GHost->m_Language->UnableToBanNoMatchesFound( Victim ) );
                                                           else if( Matches == 1 )
                                                {
                                                                   uint32_t VictimLevel = 0;
                                                                   string VictimLevelName;
                                                                   for( vector<CBNET *> :: iterator i = m_GHost->m_BNETs.begin( ); i != m_GHost->m_BNETs.end( ); ++i )
                                                                   {
                                                                           if( (*i)->GetServer( ) == LastMatch->GetSpoofedRealm( ) )
                                                                           {
                                                                                   VictimLevel = (*i)->IsLevel( LastMatch->GetName( ) );
                                                                                   VictimLevelName = (*i)->GetLevelName( VictimLevel );
                                                                                   break;
                                                                           }
                                                                   }
                                                                   if( Level >= 7 || ( Level == 5 ||  Level == 6 ) && ( ( Suffix == "hour" || Suffix == "hours" || Suffix == "h" ) || ( ( Suffix == "days" || Suffix == "d" || Suffix == "day" ) && Amount <= 5 ) ) )
                                                                            m_PairedBanAdds.push_back( PairedBanAdd( User, m_GHost->m_DB->ThreadedBanAdd( LastMatch->GetJoinedRealm( ), LastMatch->GetName( ), LastMatch->GetExternalIPString( ), m_GameName, User, Reason, BanTime, LastMatch->GetCLetter( ) ) ) );
                                                                   else
                                                                           SendChat( player, "You have no permission to ban this player" );
 
                                                }
                                                           else
                                                                   SendAllChat( m_GHost->m_Language->UnableToBanFoundMoreThanOneMatch( Victim ) );
                                                   }
                                           }
                                           else
                                           {
                                                   SendChat( player, "Bad input, expected minute(s)/hour(s)/day(s)/week(s)/month(s) but you said: " + Suffix );
                                           }
                                   }
                           }
                   }
           }
 
                        //
                        // !ANNOUNCE
                        //
 
                        else if( Command == "announce" || Command == "ann" && !m_CountDownStarted && Level >= 6 )
                        {
                                if( Payload.empty( ) || Payload == "off" )
                                {
                                        SendAllChat( m_GHost->m_Language->AnnounceMessageDisabled( ) );
                                        SetAnnounce( 0, string( ) );
                                }
                                else
                                {
                                        // extract the interval and the message
                                        // e.g. "30 hello everyone" -> interval: "30", message: "hello everyone"
 
                                        uint32_t Interval;
                                        string Message;
                                        stringstream SS;
                                        SS << Payload;
                                        SS >> Interval;
 
                                        if( SS.fail( ) || Interval == 0 )
                                                CONSOLE_Print( "[GAME: " + m_GameName + "] bad input #1 to announce command" );
                                        else
                                        {
                                                if( SS.eof( ) )
                                                        CONSOLE_Print( "[GAME: " + m_GameName + "] missing input #2 to announce command" );
                                                else
                                                {
                                                        getline( SS, Message );
                                                        string :: size_type Start = Message.find_first_not_of( " " );
 
                                                        if( Start != string :: npos )
                                                                Message = Message.substr( Start );
 
                                                        SendAllChat( m_GHost->m_Language->AnnounceMessageEnabled( ) );
                                                        SetAnnounce( Interval, Message );
                                                }
                                        }
                                }
                        }
 
                        //
                        // !AUTOSAVE
                        //
 
                        else if( Command == "autosave" && Level >= 9 )
                        {
                                if( Payload == "on" )
                                {
                                        SendAllChat( m_GHost->m_Language->AutoSaveEnabled( ) );
                                        m_AutoSave = true;
                                }
                                else if( Payload == "off" )
                                {
                                        SendAllChat( m_GHost->m_Language->AutoSaveDisabled( ) );
                                        m_AutoSave = false;
                                }
                        }
 
                        //
                        // !AUTOSTART
                        //
 
                        else if( Command == "autostart" && !m_CountDownStarted && Level >= 8 )
                        {
                                if( Payload.empty( ) || Payload == "off" )
                                {
                                        SendAllChat( m_GHost->m_Language->AutoStartDisabled( ) );
                                        m_AutoStartPlayers = 0;
                                }
                                else
                                {
                                        uint32_t AutoStartPlayers = UTIL_ToUInt32( Payload );
 
                                        if( AutoStartPlayers != 0 )
                                        {
                                                SendAllChat( m_GHost->m_Language->AutoStartEnabled( UTIL_ToString( AutoStartPlayers ) ) );
                                                m_AutoStartPlayers = AutoStartPlayers;
                                        }
                                }
                        }
 
                        //
                        // !BANLAST
                        //
 
                        else if( Command == "banlast" && m_GameLoaded && !m_GHost->m_BNETs.empty( ) && m_DBBanLast )
                        {
                                if( Level >= 7 )
                                {
                                        if( Payload.empty( ) )
                                                Payload = "Leaver";
 
                                        m_PairedBanAdds.push_back( PairedBanAdd( User, m_GHost->m_DB->ThreadedBanAdd( m_DBBanLast->GetServer( ), m_DBBanLast->GetName( ), m_DBBanLast->GetIP( ), m_GameName, User, Payload, 0, "" ) ) );
                                }
                                else
                                        SendChat( player, "Error, you have no permission to execute this command. Please use '!tbl <reason>' instead of banlast." );
                        }
 
            //
            // !TBANLAST
            //
 
            else if( ( Command == "tbanlast" || Command == "tbl" ) && m_GameLoaded && !m_GHost->m_BNETs.empty( ) && m_DBBanLast && Level >= 5 )
            {
                    if( Payload.empty( ) )
                        Payload = "Leaver";
 
                    uint32_t VictimLevel = 0;
                    string VictimLevelName;
                    for( vector<CBNET *> :: iterator i = m_GHost->m_BNETs.begin( ); i != m_GHost->m_BNETs.end( ); ++i )
                    {
                            if( (*i)->GetServer( ) == m_DBBanLast->GetServer( ) )
                            {
                                    VictimLevel = (*i)->IsLevel( m_DBBanLast->GetName( ) );
                                    VictimLevelName = (*i)->GetLevelName( VictimLevel );
                                break;
                        }
                    }
                    if( VictimLevel <= 1 || Level >= 9 )
                                        m_PairedBanAdds.push_back( PairedBanAdd( User, m_GHost->m_DB->ThreadedBanAdd( m_DBBanLast->GetServer( ), m_DBBanLast->GetName( ), m_DBBanLast->GetIP( ), m_GameName, User, Payload, 432000, "" ) ) );
                        else
                                SendChat( player, "You have no permission to ban this player" );
                        }
 
                        //
                        // !CHECK
                        //
                        else if( Command == "check" )
                        {
                                if( !Payload.empty( ) )
                                {
                                        CGamePlayer *LastMatch = NULL;
                                        uint32_t Matches = GetPlayerFromNamePartial( Payload, &LastMatch );
 
                                        if( Matches == 0 )
                                                SendAllChat( m_GHost->m_Language->UnableToCheckPlayerNoMatchesFound( Payload ) );
                                        else if( Matches == 1 )
                                        {
                                                uint32_t CLevel = 0;
                                                string CLevelName;
                                                for( vector<CBNET *> :: iterator i = m_GHost->m_BNETs.begin( ); i != m_GHost->m_BNETs.end( ); ++i )
                                                {
                                                        if( (*i)->GetServer( ) == player->GetSpoofedRealm( ) )
                                                        {
                                                                CLevel = (*i)->IsLevel( LastMatch->GetName( ) );
                                                                CLevelName = (*i)->GetLevelName( CLevel );
                                                                break;
                                                        }
                                                }
                                                SendAllChat( "[" + LastMatch->GetName( ) + "] (P: " + ( LastMatch->GetNumPings( ) > 0 ? UTIL_ToString( LastMatch->GetPing( m_GHost->m_LCPings ) ) + "ms" : "N/A" ) + ") (F: " + LastMatch->GetCLetter( )+ ") (Role: " + ( CLevelName.empty( ) ? "unknown" : CLevelName ) + ") (SpoofChecked: " + ( LastMatch->GetSpoofed( ) ? "Yes" : "No" ) + ") (Realm: " + ( LastMatch->GetSpoofedRealm( ).empty( ) ? "N/A" : LastMatch->GetSpoofedRealm( ) ) + ")" );
                                        }
                                        else
                                                SendAllChat( m_GHost->m_Language->UnableToCheckPlayerFoundMoreThanOneMatch( Payload ) );
                                }
                                else
                                        SendAllChat( "[" + User + "] (P: " + ( player->GetNumPings( ) > 0 ? UTIL_ToString( player->GetPing( m_GHost->m_LCPings ) ) + "ms" : "N/A" ) + ") (F: " + player->GetCLetter( ) + ") (Role: " + (LevelName.empty() ? "unknown" : LevelName) + ") (SpoofChecked: " + ( player->GetSpoofed( ) ? "Yes" : "No" ) + ") (Realm: " + ( player->GetSpoofedRealm( ).empty( ) ? "N/A" : player->GetSpoofedRealm( ) ) +")" );
                        }
 
                        //
                        // !CHECKBAN
                        //
                        else if( Command == "checkban" && !Payload.empty( ) && !m_GHost->m_BNETs.empty( ) )
                        {
                                for( vector<CBNET *> :: iterator i = m_GHost->m_BNETs.begin( ); i != m_GHost->m_BNETs.end( ); ++i )
                                        m_PairedBanChecks.push_back( PairedBanCheck( User, m_GHost->m_DB->ThreadedBanCheck( (*i)->GetServer( ), Payload, string( ) ) ) );
                        }
 
                        //
                        // !CLEARHCL
                        //
                        else if( Command == "clearhcl" && !m_CountDownStarted && Level >= 8 )
                        {
                                m_HCLCommandString.clear( );
                                SendAllChat( m_GHost->m_Language->ClearingHCL( ) );
                        }
 
                        //
                        // !CLOSE (close slot)
                        //
                        else if( Command == "close" && !Payload.empty( ) && !m_GameLoading && !m_GameLoaded && Level >= 8 )
                        {
                                // close as many slots as specified, e.g. "5 10" closes slots 5 and 10
 
                                stringstream SS;
                                SS << Payload;
 
                                while( !SS.eof( ) )
                                {
                                        uint32_t SID;
                                        SS >> SID;
 
                                        if( SS.fail( ) )
                                        {
                                                CONSOLE_Print( "[GAME: " + m_GameName + "] bad input to close command" );
                                                break;
                                        }
                                        else
                                                CloseSlot( (unsigned char)( SID - 1 ), true );
                                }
                        }
 
                        //
                        // !CLOSEALL
                        //
                        else if( Command == "closeall" && !m_GameLoading && !m_GameLoaded && Level >= 9 )
                                CloseAllSlots( );
 
                        //
                        // !COMP (computer slot)
                        //
                        else if( Command == "comp" && !Payload.empty( ) && !m_GameLoading && !m_GameLoaded && !m_SaveGame && Level >= 8 )
                        {
                                // extract the slot and the skill
                                // e.g. "1 2" -> slot: "1", skill: "2"
 
                                uint32_t Slot;
                                uint32_t Skill = 1;
                                stringstream SS;
                                SS << Payload;
                                SS >> Slot;
 
                                if( SS.fail( ) )
                                        CONSOLE_Print( "[GAME: " + m_GameName + "] bad input #1 to comp command" );
                                else
                                {
                                        if( !SS.eof( ) )
                                                SS >> Skill;
 
                                        if( SS.fail( ) )
                                                CONSOLE_Print( "[GAME: " + m_GameName + "] bad input #2 to comp command" );
                                        else
                                                ComputerSlot( (unsigned char)( Slot - 1 ), (unsigned char)Skill, true );
                                }
                        }
 
                        //
                        // !COMPCOLOUR (computer colour change)
                        //
                        else if( Command == "compcolour" && !Payload.empty( ) && !m_GameLoading && !m_GameLoaded && !m_SaveGame && Level >= 8 )
                        {
                                // extract the slot and the colour
                                // e.g. "1 2" -> slot: "1", colour: "2"
 
                                uint32_t Slot;
                                uint32_t Colour;
                                stringstream SS;
                                SS << Payload;
                                SS >> Slot;
 
                                if( SS.fail( ) )
                                        CONSOLE_Print( "[GAME: " + m_GameName + "] bad input #1 to compcolour command" );
                                else
                                {
                                        if( SS.eof( ) )
                                                CONSOLE_Print( "[GAME: " + m_GameName + "] missing input #2 to compcolour command" );
                                        else
                                        {
                                                SS >> Colour;
 
                                                if( SS.fail( ) )
                                                        CONSOLE_Print( "[GAME: " + m_GameName + "] bad input #2 to compcolour command" );
                                                else
                                                {
                                                        unsigned char SID = (unsigned char)( Slot - 1 );
 
                                                        if( !( m_Map->GetMapOptions( ) & MAPOPT_FIXEDPLAYERSETTINGS ) && Colour < 12 && SID < m_Slots.size( ) )
                                                        {
                                                                if( m_Slots[SID].GetSlotStatus( ) == SLOTSTATUS_OCCUPIED && m_Slots[SID].GetComputer( ) == 1 )
                                                                        ColourSlot( SID, Colour );
                                                        }
                                                }
                                        }
                                }
                        }
 
                        //
                        // !COMPHANDICAP (computer handicap change)
                        //
                        else if( Command == "comphandicap" && !Payload.empty( ) && !m_GameLoading && !m_GameLoaded && !m_SaveGame && Level >= 8 )
                        {
                                // extract the slot and the handicap
                                // e.g. "1 50" -> slot: "1", handicap: "50"
 
                                uint32_t Slot;
                                uint32_t Handicap;
                                stringstream SS;
                                SS << Payload;
                                SS >> Slot;
 
                                if( SS.fail( ) )
                                        CONSOLE_Print( "[GAME: " + m_GameName + "] bad input #1 to comphandicap command" );
                                else
                                {
                                        if( SS.eof( ) )
                                                CONSOLE_Print( "[GAME: " + m_GameName + "] missing input #2 to comphandicap command" );
                                        else
                                        {
                                                SS >> Handicap;
 
                                                if( SS.fail( ) )
                                                        CONSOLE_Print( "[GAME: " + m_GameName + "] bad input #2 to comphandicap command" );
                                                else
                                                {
                                                        unsigned char SID = (unsigned char)( Slot - 1 );
 
                                                        if( !( m_Map->GetMapOptions( ) & MAPOPT_FIXEDPLAYERSETTINGS ) && ( Handicap == 50 || Handicap == 60 || Handicap == 70 || Handicap == 80 || Handicap == 90 || Handicap == 100 ) && SID < m_Slots.size( ) )
                                                        {
                                                                if( m_Slots[SID].GetSlotStatus( ) == SLOTSTATUS_OCCUPIED && m_Slots[SID].GetComputer( ) == 1 )
                                                                {
                                                                        m_Slots[SID].SetHandicap( (unsigned char)Handicap );
                                                                        SendAllSlotInfo( );
                                                                }
                                                        }
                                                }
                                        }
                                }
                        }
 
                        //
                        // !COMPRACE (computer race change)
                        //
                        else if( Command == "comprace" && !Payload.empty( ) && !m_GameLoading && !m_GameLoaded && !m_SaveGame && Level >= 8 )
                        {
                                // extract the slot and the race
                                // e.g. "1 human" -> slot: "1", race: "human"
 
                                uint32_t Slot;
                                string Race;
                                stringstream SS;
                                SS << Payload;
                                SS >> Slot;
 
                                if( SS.fail( ) )
                                        CONSOLE_Print( "[GAME: " + m_GameName + "] bad input #1 to comprace command" );
                                else
                                {
                                        if( SS.eof( ) )
                                                CONSOLE_Print( "[GAME: " + m_GameName + "] missing input #2 to comprace command" );
                                        else
                                        {
                                                getline( SS, Race );
                                                string :: size_type Start = Race.find_first_not_of( " " );
 
                                                if( Start != string :: npos )
                                                        Race = Race.substr( Start );
 
                                                transform( Race.begin( ), Race.end( ), Race.begin( ), (int(*)(int))tolower );
                                                unsigned char SID = (unsigned char)( Slot - 1 );
 
                                                if( !( m_Map->GetMapOptions( ) & MAPOPT_FIXEDPLAYERSETTINGS ) && !( m_Map->GetMapFlags( ) & MAPFLAG_RANDOMRACES ) && SID < m_Slots.size( ) )
                                                {
                                                        if( m_Slots[SID].GetSlotStatus( ) == SLOTSTATUS_OCCUPIED && m_Slots[SID].GetComputer( ) == 1 )
                                                        {
                                                                if( Race == "human" )
                                                                {
                                                                        m_Slots[SID].SetRace( SLOTRACE_HUMAN | SLOTRACE_SELECTABLE );
                                                                        SendAllSlotInfo( );
                                                                }
                                                                else if( Race == "orc" )
                                                                {
                                                                        m_Slots[SID].SetRace( SLOTRACE_ORC | SLOTRACE_SELECTABLE );
                                                                        SendAllSlotInfo( );
                                                                }
                                                                else if( Race == "night elf" )
                                                                {
                                                                        m_Slots[SID].SetRace( SLOTRACE_NIGHTELF | SLOTRACE_SELECTABLE );
                                                                        SendAllSlotInfo( );
                                                                }
                                                                else if( Race == "undead" )
                                                                {
                                                                        m_Slots[SID].SetRace( SLOTRACE_UNDEAD | SLOTRACE_SELECTABLE );
                                                                        SendAllSlotInfo( );
                                                                }
                                                                else if( Race == "random" )
                                                                {
                                                                        m_Slots[SID].SetRace( SLOTRACE_RANDOM | SLOTRACE_SELECTABLE );
                                                                        SendAllSlotInfo( );
                                                                }
                                                                else
                                                                        CONSOLE_Print( "[GAME: " + m_GameName + "] unknown race [" + Race + "] sent to comprace command" );
                                                        }
                                                }
                                        }
                                }
                        }
 
                        //
                        // !COMPTEAM (computer team change)
                        //
                        else if( Command == "compteam" && !Payload.empty( ) && !m_GameLoading && !m_GameLoaded && !m_SaveGame && Level >= 8 )
                        {
                                // extract the slot and the team
                                // e.g. "1 2" -> slot: "1", team: "2"
 
                                uint32_t Slot;
                                uint32_t Team;
                                stringstream SS;
                                SS << Payload;
                                SS >> Slot;
 
                                if( SS.fail( ) )
                                        CONSOLE_Print( "[GAME: " + m_GameName + "] bad input #1 to compteam command" );
                                else
                                {
                                        if( SS.eof( ) )
                                                CONSOLE_Print( "[GAME: " + m_GameName + "] missing input #2 to compteam command" );
                                        else
                                        {
                                                SS >> Team;
 
                                                if( SS.fail( ) )
                                                        CONSOLE_Print( "[GAME: " + m_GameName + "] bad input #2 to compteam command" );
                                                else
                                                {
                                                        unsigned char SID = (unsigned char)( Slot - 1 );
 
                                                        if( !( m_Map->GetMapOptions( ) & MAPOPT_FIXEDPLAYERSETTINGS ) && Team < 12 && SID < m_Slots.size( ) )
                                                        {
                                                                if( m_Slots[SID].GetSlotStatus( ) == SLOTSTATUS_OCCUPIED && m_Slots[SID].GetComputer( ) == 1 )
                                                                {
                                                                        m_Slots[SID].SetTeam( (unsigned char)( Team - 1 ) );
                                                                        SendAllSlotInfo( );
                                                                }
                                                        }
                                                }
                                        }
                                }
                        }
 
                        //
                        // !DBSTATUS
                        //
                        else if( Command == "dbstatus" && Level >= 9 )
                                SendAllChat( m_GHost->m_DB->GetStatus( ) );
 
                        //
                        // !DOWNLOAD
                        // !DL
                        //
                        else if( ( Command == "download" || Command == "dl" ) && !Payload.empty( ) && !m_GameLoading && !m_GameLoaded && Level >= 8 )
                        {
                                CGamePlayer *LastMatch = NULL;
                                uint32_t Matches = GetPlayerFromNamePartial( Payload, &LastMatch );
 
                                if( Matches == 0 )
                                        SendAllChat( m_GHost->m_Language->UnableToStartDownloadNoMatchesFound( Payload ) );
                                else if( Matches == 1 )
                                {
                                        if( !LastMatch->GetDownloadStarted( ) && !LastMatch->GetDownloadFinished( ) )
                                        {
                                                unsigned char SID = GetSIDFromPID( LastMatch->GetPID( ) );
 
                                                if( SID < m_Slots.size( ) && m_Slots[SID].GetDownloadStatus( ) != 100 )
                                                {
                                                        // inform the client that we are willing to send the map
 
                                                        CONSOLE_Print( "[GAME: " + m_GameName + "] map download started for player [" + LastMatch->GetName( ) + "]" );
                                                        Send( LastMatch, m_Protocol->SEND_W3GS_STARTDOWNLOAD( GetHostPID( ) ) );
                                                        LastMatch->SetDownloadAllowed( true );
                                                        LastMatch->SetDownloadStarted( true );
                                                        LastMatch->SetStartedDownloadingTicks( GetTicks( ) );
                                                }
                                        }
                                }
                                else
                                        SendAllChat( m_GHost->m_Language->UnableToStartDownloadFoundMoreThanOneMatch( Payload ) );
                        }
 
                        //
                        // !DROP
                        //
                        else if( Command == "drop" && m_GameLoaded && Level >= 6 )
                                StopLaggers( "lagged out (dropped by admin)" );
 
                        //
                        // !END
                        //
                        else if( Command == "end" && m_GameLoaded && Level >= 8 )
                        {
                                CONSOLE_Print( "[GAME: " + m_GameName + "] is over (admin ended game)" );
                                StopPlayers( "was disconnected (admin ended game)" );
                        }
 
                        //
                        // !FAKEPLAYER
                        //
                        else if( Command == "fakeplayer" && !m_CountDownStarted && Level >= 8 && !m_GHost->m_ObserverFake )
                        {
                                if( m_FakePlayerPID == 255 )
                                        CreateFakePlayer( );
                                else
                                        DeleteFakePlayer( );
                        }
 
                        //
                        // !FROM
                        //
                        else if( Command == "from" && Level >= 5 )
                        {
                                string Froms;
 
                                for( vector<CGamePlayer *> :: iterator i = m_Players.begin( ); i != m_Players.end( ); ++i )
                                {
                                        // we reverse the byte order on the IP because it's stored in network byte order
 
                                        Froms += (*i)->GetNameTerminated( );
                                        Froms += ": (";
                                        Froms += (*i)->GetCLetter( );
                                        Froms += ")";
 
                                        if( i != m_Players.end( ) - 1 )
                                                Froms += ", ";
 
                                        if( ( m_GameLoading || m_GameLoaded ) && Froms.size( ) > 100 )
                                        {
                                                // cut the text into multiple lines ingame
 
                                                SendAllChat( Froms );
                                                Froms.clear( );
                                        }
                                }
 
                                if( !Froms.empty( ) )
                                        SendAllChat( Froms );
                        }
 
                        //
                        // !HCL
                        //
                        else if( Command == "hcl" && !m_CountDownStarted && Level >= 8 )
                        {
                                if( !Payload.empty( ) )
                                {
                                        if( Payload.size( ) <= m_Slots.size( ) )
                                        {
                                                string HCLChars = "abcdefghijklmnopqrstuvwxyz0123456789 -=,.";
 
                                                if( Payload.find_first_not_of( HCLChars ) == string :: npos )
                                                {
                                                        m_HCLCommandString = Payload;
                                                        SendAllChat( m_GHost->m_Language->SettingHCL( m_HCLCommandString ) );
                                                }
                                                else
                                                        SendAllChat( m_GHost->m_Language->UnableToSetHCLInvalid( ) );
                                        }
                                        else
                                                SendAllChat( m_GHost->m_Language->UnableToSetHCLTooLong( ) );
                                }
                                else
                                        SendAllChat( m_GHost->m_Language->TheHCLIs( m_HCLCommandString ) );
                        }
 
                        //
                        // !HOLD (hold a slot for someone)
                        //
                        else if( Command == "hold" && !Payload.empty( ) && !m_GameLoading && !m_GameLoaded && Level >= 8 )
                        {
                                // hold as many players as specified, e.g. "Varlock Kilranin" holds players "Varlock" and "Kilranin"
 
                                stringstream SS;
                                SS << Payload;
 
                                while( !SS.eof( ) )
                                {
                                        string HoldName;
                                        SS >> HoldName;
 
                                        if( SS.fail( ) )
                                        {
                                                CONSOLE_Print( "[GAME: " + m_GameName + "] bad input to hold command" );
                                                break;
                                        }
                                        else
                                        {
                                                SendAllChat( m_GHost->m_Language->AddedPlayerToTheHoldList( HoldName ) );
                                                AddToReserved( HoldName );
                                        }
                                }
                        }
 
                        //
                        // !KICK (kick a player)
                        //
                        else if( Command == "kick" && !Payload.empty( ) && Level >= 5 )
                        {
                                CGamePlayer *LastMatch = NULL;
                                uint32_t Matches = GetPlayerFromNamePartial( Payload, &LastMatch );
 
                                if( Matches == 0 )
                                        SendAllChat( m_GHost->m_Language->UnableToKickNoMatchesFound( Payload ) );
                                else if( Matches == 1 )
                                {
                                        uint32_t VictimLevel = 0;
                                        string VictimLevelName;
                                        for( vector<CBNET *> :: iterator i = m_GHost->m_BNETs.begin( ); i != m_GHost->m_BNETs.end( ); ++i )
                                        {
                                                if( (*i)->GetServer( ) == LastMatch->GetSpoofedRealm( ) )
                                                {
                                                        VictimLevel = (*i)->IsLevel( LastMatch->GetName( ) );
                                                        VictimLevelName = (*i)->GetLevelName( VictimLevel );
                                                        break;
                                                }
                                        }
                                        if( VictimLevel <= 1 || Level >= 9 )
                                        {
                                                LastMatch->SetDeleteMe( true );
                                                LastMatch->SetLeftReason( m_GHost->m_Language->WasKickedByPlayer( User ) );
 
                                                if( !m_GameLoading && !m_GameLoaded )
                                                        LastMatch->SetLeftCode( PLAYERLEAVE_LOBBY );
                                                else
                                                        LastMatch->SetLeftCode( PLAYERLEAVE_LOST );
 
                                                if( !m_GameLoading && !m_GameLoaded )
                                                {
                                                        OpenSlot( GetSIDFromPID( LastMatch->GetPID( ) ), false );
                                                        m_Balanced = false;
                                                }
                                        }
                                        else
                                                SendChat( player, "You have no permission to kick this player" );
                                }
                                else
                                        SendAllChat( m_GHost->m_Language->UnableToKickFoundMoreThanOneMatch( Payload ) );
                        }
 
                        //
                        // !LATENCY (set game latency)
                        //
                        else if( Command == "latency" && Level >= 9 )
                        {
                                if( Payload.empty( ) )
                                        SendAllChat( m_GHost->m_Language->LatencyIs( UTIL_ToString( m_Latency ) ) );
                                else
                                {
                                        m_Latency = UTIL_ToUInt32( Payload );
 
                                        if( m_Latency <= 20 )
                                        {
                                                m_Latency = 20;
                                                SendAllChat( m_GHost->m_Language->SettingLatencyToMinimum( "20" ) );
                                        }
                                        else if( m_Latency >= 500 )
                                        {
                                                m_Latency = 500;
                                                SendAllChat( m_GHost->m_Language->SettingLatencyToMaximum( "500" ) );
                                        }
                                        else
                                                SendAllChat( m_GHost->m_Language->SettingLatencyTo( UTIL_ToString( m_Latency ) ) );
                                }
                        }
 
                        //
                        // !GAMELOCK
                        //
                        else if( Command == "gamelock" && Level == 10 )
                        {
                                SendAllChat( m_GHost->m_Language->GameLocked( ) );
                                m_Locked = true;
                        }
 
                        //
                        // !MESSAGES
                        //
                        else if( Command == "messages" && Level >= 9 )
                        {
                                if( Payload == "on" )
                                {
                                        SendAllChat( m_GHost->m_Language->LocalAdminMessagesEnabled( ) );
                                        m_LocalAdminMessages = true;
                                }
                                else if( Payload == "off" )
                                {
                                        SendAllChat( m_GHost->m_Language->LocalAdminMessagesDisabled( ) );
                                        m_LocalAdminMessages = false;
                                }
                        }
 
            //
            // !OHBALANCE
            //
            else if( Command == "ohbalance" && Level == 10 && !Payload.empty() )
            {
                    if( Payload == "on" )
                    {
                            SendAllChat( "Enabled autobalance" );
                            m_GHost->m_OHBalance = true;
                    }
                    else if( Payload == "off" )
                    {
                            SendAllChat( "Disabled autobalance" );
                            m_GHost->m_OHBalance = false;
                    }
            }
 
                        //
                        // !MUTE
                        //
                        else if( Command == "mute" && Level >= 5 )
                        {
                                CGamePlayer *LastMatch = NULL;
                                uint32_t Matches = GetPlayerFromNamePartial( Payload, &LastMatch );
 
                                        if( Matches == 0 )
                                                SendAllChat( m_GHost->m_Language->UnableToMuteNoMatchesFound( Payload ) );
                                        else if( Matches == 1 )
                                        {
                                                uint32_t VictimLevel = 0;
                                                string VictimLevelName;
                                                for( vector<CBNET *> :: iterator i = m_GHost->m_BNETs.begin( ); i != m_GHost->m_BNETs.end( ); ++i )
                                                {
                                                        if( (*i)->GetServer( ) == LastMatch->GetSpoofedRealm( ) )
                                                        {
                                                                VictimLevel = (*i)->IsLevel( LastMatch->GetName( ) );
                                                                VictimLevelName = (*i)->GetLevelName( VictimLevel );
                                                                break;
                                                        }
                                                }
                                                if( VictimLevel <= 1 || Level >= 9 )
                                                {
                                                        SendAllChat( m_GHost->m_Language->MutedPlayer( LastMatch->GetName( ), User ) );
                                                        LastMatch->SetMuted( true );
                                                }
                                                else
                                                        SendChat( player, "You have no permission to mute this player" );
                                        }
                                        else
                                                SendAllChat( m_GHost->m_Language->UnableToMuteFoundMoreThanOneMatch( Payload ) );
                        }
 
                        //
                        // !MUTEALL
                        //
                        else if( Command == "muteall" && m_GameLoaded && Level >= 6 )
                        {
                                SendAllChat( m_GHost->m_Language->GlobalChatMuted( ) );
                                m_MuteAll = true;
                        }
 
                        //
                        // !OPEN (open slot)
                        //
                        else if( Command == "open" && !Payload.empty( ) && !m_GameLoading && !m_GameLoaded && Level >= 6 )
                        {
                                // open as many slots as specified, e.g. "5 10" opens slots 5 and 10
 
                                stringstream SS;
                                SS << Payload;
 
                                while( !SS.eof( ) )
                                {
                                        uint32_t SID;
                                        SS >> SID;
 
                                        if( SS.fail( ) )
                                        {
                                                CONSOLE_Print( "[GAME: " + m_GameName + "] bad input to open command" );
                                                break;
                                        }
                                        else
                                        {
                                                OpenSlot( (unsigned char)( SID - 1 ), true );
                                                m_Balanced = false;
                                        }
                                }
                        }
 
                        //
                        // !OPENALL
                        //
                        else if( Command == "openall" && !m_GameLoading && !m_GameLoaded && Level >= 8 )
                                OpenAllSlots( );
 
                        //
                        // !OWNER (set game owner)
                        //
                        else if( Command == "owner" && Level >= 9 )
                        {
                                if( Level >= 8 || !GetPlayerFromName( m_OwnerName, false ) )
                                {
                                        if( !Payload.empty( ) )
                                        {
                                                SendAllChat( m_GHost->m_Language->SettingGameOwnerTo( Payload ) );
                                                m_OwnerName = Payload;
                                        }
                                        else
                                        {
                                                SendAllChat( m_GHost->m_Language->SettingGameOwnerTo( User ) );
                                                m_OwnerName = User;
                                        }
                                }
                                else
                                        SendAllChat( m_GHost->m_Language->UnableToSetGameOwner( m_OwnerName ) );
                        }
 
                        //
                        // !PING
                        //
                        else if( Command == "ping" && Level >= 5 )
                        {
                                // kick players with ping higher than payload if payload isn't empty
                                // we only do this if the game hasn't started since we don't want to kick players from a game in progress
 
                                uint32_t Kicked = 0;
                                uint32_t KickPing = 0;
 
                                if( !m_GameLoading && !m_GameLoaded && !Payload.empty( ) )
                                        KickPing = UTIL_ToUInt32( Payload );
 
                                // copy the m_Players vector so we can sort by descending ping so it's easier to find players with high pings
 
                                vector<CGamePlayer *> SortedPlayers = m_Players;
                                sort( SortedPlayers.begin( ), SortedPlayers.end( ), CGamePlayerSortDescByPing( ) );
                                string Pings;
 
                                for( vector<CGamePlayer *> :: iterator i = SortedPlayers.begin( ); i != SortedPlayers.end( ); ++i )
                                {
                                        Pings += (*i)->GetNameTerminated( );
                                        Pings += ": ";
 
                                        if( (*i)->GetNumPings( ) > 0 )
                                        {
                                                Pings += UTIL_ToString( (*i)->GetPing( m_GHost->m_LCPings ) );
 
                                                if( !m_GameLoading && !m_GameLoaded && !(*i)->GetReserved( ) && KickPing > 0 && (*i)->GetPing( m_GHost->m_LCPings ) > KickPing )
                                                {
                                                        (*i)->SetDeleteMe( true );
                                                        (*i)->SetLeftReason( "was kicked for excessive ping " + UTIL_ToString( (*i)->GetPing( m_GHost->m_LCPings ) ) + " > " + UTIL_ToString( KickPing ) );
                                                        (*i)->SetLeftCode( PLAYERLEAVE_LOBBY );
                                                        OpenSlot( GetSIDFromPID( (*i)->GetPID( ) ), false );
                                                        Kicked++;
                                                }
 
                                                Pings += "ms";
                                        }
                                        else
                                                Pings += "N/A";
 
                                        if( i != SortedPlayers.end( ) - 1 )
                                                Pings += ", ";
 
                                        if( ( m_GameLoading || m_GameLoaded ) && Pings.size( ) > 100 )
                                        {
                                                // cut the text into multiple lines ingame
 
                                                SendAllChat( Pings );
                                                Pings.clear( );
                                        }
                                }
 
                                if( !Pings.empty( ) )
                                        SendAllChat( Pings );
 
                                if( Kicked > 0 )
                                        SendAllChat( m_GHost->m_Language->KickingPlayersWithPingsGreaterThan( UTIL_ToString( Kicked ), UTIL_ToString( KickPing ) ) );
                        }
 
                        //
                        // !PRIV (rehost as private game)
                        //
                        else if( Command == "priv" && !Payload.empty( ) && !m_CountDownStarted && !m_SaveGame && Level >= 8 )
                        {
                                if( Payload.length() < 31 )
                                {
                                        CONSOLE_Print( "[GAME: " + m_GameName + "] trying to rehost as private game [" + Payload + "]" );
                                        SendAllChat( m_GHost->m_Language->TryingToRehostAsPrivateGame( Payload ) );
                                        m_GameState = GAME_PRIVATE;
                                        m_LastGameName = m_GameName;
                                        m_GameName = Payload;
                                        m_HostCounter = m_GHost->m_HostCounter++;
                                        m_RefreshError = false;
                                        m_RefreshRehosted = true;
                                        m_GHost->SaveHostCounter();
                                        m_HostCounter = m_GHost->m_HostCounter;
 
                                        for( vector<CBNET *> :: iterator i = m_GHost->m_BNETs.begin( ); i != m_GHost->m_BNETs.end( ); ++i )
                                        {
                                                // unqueue any existing game refreshes because we're going to assume the next successful game refresh indicates that the rehost worked
                                                // this ignores the fact that it's possible a game refresh was just sent and no response has been received yet
                                                // we assume this won't happen very often since the only downside is a potential false positive
 
                                                (*i)->UnqueueGameRefreshes( );
                                                (*i)->QueueGameUncreate( );
                                                (*i)->QueueEnterChat( );
 
                                                // we need to send the game creation message now because private games are not refreshed
 
                                                (*i)->QueueGameCreate( m_GameState, m_GameName, string( ), m_Map, NULL, m_HostCounter );
 
                                                if( (*i)->GetPasswordHashType( ) != "pvpgn" )
                                                        (*i)->QueueEnterChat( );
                                        }
 
                                        m_CreationTime = GetTime( );
                                        m_LastRefreshTime = GetTime( );
                                }
                                else
                                        SendAllChat( m_GHost->m_Language->UnableToCreateGameNameTooLong( Payload ) );
                        }
 
                        //
                        // !PUB (rehost as public game)
                        //
                        else if( Command == "pub" && !Payload.empty( ) && !m_CountDownStarted && !m_SaveGame && Level >= 8 )
                        {
                                if( Payload.length() < 31 )
                                {
                                        CONSOLE_Print( "[GAME: " + m_GameName + "] trying to rehost as public game [" + Payload + "]" );
                                        SendAllChat( m_GHost->m_Language->TryingToRehostAsPublicGame( Payload ) );
                                        m_GameState = GAME_PUBLIC;
                                        m_LastGameName = m_GameName;
                                        m_GameName = Payload;
                                        m_HostCounter = m_GHost->m_HostCounter++;
                                        m_RefreshError = false;
                                        m_RefreshRehosted = true;
                                        m_GHost->SaveHostCounter();
                                        m_HostCounter = m_GHost->m_HostCounter;
                                        for( vector<CBNET *> :: iterator i = m_GHost->m_BNETs.begin( ); i != m_GHost->m_BNETs.end( ); ++i )
                                        {
                                                // unqueue any existing game refreshes because we're going to assume the next successful game refresh indicates that the rehost worked
                                                // this ignores the fact that it's possible a game refresh was just sent and no response has been received yet
                                                // we assume this won't happen very often since the only downside is a potential false positive
 
                                                (*i)->UnqueueGameRefreshes( );
                                                (*i)->QueueGameUncreate( );
                                                (*i)->QueueEnterChat( );
 
                                                // the game creation message will be sent on the next refresh
                                        }
 
                                        m_CreationTime = GetTime( );
                                        m_LastRefreshTime = GetTime( );
                                }
                                else
                                        SendAllChat( m_GHost->m_Language->UnableToCreateGameNameTooLong( Payload ) );
                        }
 
                        //
                        // !REFRESH (turn on or off refresh messages)
                        //
                        else if( Command == "refresh" && !m_CountDownStarted && Level == 10 )
                        {
                                if( Payload == "on" )
                                {
                                        SendAllChat( m_GHost->m_Language->RefreshMessagesEnabled( ) );
                                        m_RefreshMessages = true;
                                }
                                else if( Payload == "off" )
                                {
                                        SendAllChat( m_GHost->m_Language->RefreshMessagesDisabled( ) );
                                        m_RefreshMessages = false;
                                }
                        }
 
                        //
                        // !SAY
                        //
                        else if( Command == "say" && !Payload.empty( ) && Level >= 8 )
                        {
                                for( vector<CBNET *> :: iterator i = m_GHost->m_BNETs.begin( ); i != m_GHost->m_BNETs.end( ); ++i )
                                        (*i)->QueueChatCommand( Payload );
 
                                HideCommand = true;
                        }
 
                        //
                        // !SENDLAN
                        //
                        else if( Command == "sendlan" && !Payload.empty( ) && !m_CountDownStarted && Level >= 9 )
                        {
                                // extract the ip and the port
                                // e.g. "1.2.3.4 6112" -> ip: "1.2.3.4", port: "6112"
 
                                string IP;
                                uint32_t Port = 6112;
                                stringstream SS;
                                SS << Payload;
                                SS >> IP;
 
                                if( !SS.eof( ) )
                                        SS >> Port;
 
                                if( SS.fail( ) )
                                        CONSOLE_Print( "[GAME: " + m_GameName + "] bad inputs to sendlan command" );
                                else
                                {
                                        // construct a fixed host counter which will be used to identify players from this "realm" (i.e. LAN)
                                        // the fixed host counter's 4 most significant bits will contain a 4 bit ID (0-15)
                                        // the rest of the fixed host counter will contain the 28 least significant bits of the actual host counter
                                        // since we're destroying 4 bits of information here the actual host counter should not be greater than 2^28 which is a reasonable assumption
                                        // when a player joins a game we can obtain the ID from the received host counter
                                        // note: LAN broadcasts use an ID of 0, battle.net refreshes use an ID of 1-10, the rest are unused
 
                                        uint32_t FixedHostCounter = m_HostCounter & 0x0FFFFFFF;
 
                                        // we send 12 for SlotsTotal because this determines how many PID's Warcraft 3 allocates
                                        // we need to make sure Warcraft 3 allocates at least SlotsTotal + 1 but at most 12 PID's
                                        // this is because we need an extra PID for the virtual host player (but we always delete the virtual host player when the 12th person joins)
                                        // however, we can't send 13 for SlotsTotal because this causes Warcraft 3 to crash when sharing control of units
                                        // nor can we send SlotsTotal because then Warcraft 3 crashes when playing maps with less than 12 PID's (because of the virtual host player taking an extra PID)
                                        // we also send 12 for SlotsOpen because Warcraft 3 assumes there's always at least one player in the game (the host)
                                        // so if we try to send accurate numbers it'll always be off by one and results in Warcraft 3 assuming the game is full when it still needs one more player
                                        // the easiest solution is to simply send 12 for both so the game will always show up as (1/12) players
 
                                        if( m_SaveGame )
                                        {
                                                // note: the PrivateGame flag is not set when broadcasting to LAN (as you might expect)
 
                                                uint32_t MapGameType = MAPGAMETYPE_SAVEDGAME;
                                                BYTEARRAY MapWidth;
                                                MapWidth.push_back( 0 );
                                                MapWidth.push_back( 0 );
                                                BYTEARRAY MapHeight;
                                                MapHeight.push_back( 0 );
                                                MapHeight.push_back( 0 );
                                                m_GHost->m_UDPSocket->SendTo( IP, Port, m_Protocol->SEND_W3GS_GAMEINFO( m_GHost->m_TFT, m_GHost->m_LANWar3Version, UTIL_CreateByteArray( MapGameType, false ), m_Map->GetMapGameFlags( ), MapWidth, MapHeight, m_GameName, m_GHost->m_BNETs[0]->GetUserName(), GetTime( ) - m_CreationTime, "Save\\Multiplayer\\" + m_SaveGame->GetFileNameNoPath( ), m_SaveGame->GetMagicNumber( ), 12, 12, m_HostPort, FixedHostCounter, m_EntryKey ) );
                                        }
                                        else
                                        {
                                                // note: the PrivateGame flag is not set when broadcasting to LAN (as you might expect)
                                                // note: we do not use m_Map->GetMapGameType because none of the filters are set when broadcasting to LAN (also as you might expect)
 
                                                uint32_t MapGameType = MAPGAMETYPE_UNKNOWN0;
                                                m_GHost->m_UDPSocket->SendTo( IP, Port, m_Protocol->SEND_W3GS_GAMEINFO( m_GHost->m_TFT, m_GHost->m_LANWar3Version, UTIL_CreateByteArray( MapGameType, false ), m_Map->GetMapGameFlags( ), m_Map->GetMapWidth( ), m_Map->GetMapHeight( ), m_GameName, m_GHost->m_BNETs[0]->GetUserName(), GetTime( ) - m_CreationTime, m_Map->GetMapPath( ), m_Map->GetMapCRC( ), 12, 12, m_HostPort, FixedHostCounter, m_EntryKey ) );
                                        }
                                }
                        }
 
                        //
                        // !SP
                        //
                        else if( Command == "sp" && !m_CountDownStarted && Level >= 8 )
                        {
                                SendAllChat( m_GHost->m_Language->ShufflingPlayers( ) );
                                ShuffleSlots( );
                        }
 
                        //
                        // !START
                        //
                        else if( Command == "start" && !m_CountDownStarted && Level >= 8 )
                        {
                                // if the player sent "!start force" skip the checks and start the countdown
                                // otherwise check that the game is ready to start
 
                                if( Payload == "force" )
                                        StartCountDown( true );
                                else
                                {
                                        if( GetTicks( ) - m_LastPlayerLeaveTicks >= 2000 )
                                                StartCountDown( false );
                                        else
                                                SendAllChat( m_GHost->m_Language->CountDownAbortedSomeoneLeftRecently( ) );
                                }
                        }
 
                        //
                        // !SWAP (swap slots)
                        //
                        else if( Command == "swap" && !Payload.empty( ) && !m_GameLoading && !m_GameLoaded && !m_CountDownStarted && Level >= 6 )
                        {
                                uint32_t SID1;
                                uint32_t SID2;
                                stringstream SS;
                                SS << Payload;
                                SS >> SID1;
 
                                if( SS.fail( ) )
                                        CONSOLE_Print( "[GAME: " + m_GameName + "] bad input #1 to swap command" );
                                else
                                {
                                        if( SS.eof( ) )
                                                CONSOLE_Print( "[GAME: " + m_GameName + "] missing input #2 to swap command" );
                                        else
                                        {
                                                SS >> SID2;
 
                                                if( SS.fail( ) )
                                                        CONSOLE_Print( "[GAME: " + m_GameName + "] bad input #2 to swap command" );
                                                else
                                                {
                                                        SwapSlots( (unsigned char)( SID1 - 1 ), (unsigned char)( SID2 - 1 ) );
                                                        m_Balanced = false;
                                                }
                                        }
                                }
                        }
 
                        //
                        // !SYNCLIMIT
                        //
                        else if( Command == "synclimit" && Level >= 9 )
                        {
                                if( Payload.empty( ) )
                                        SendAllChat( m_GHost->m_Language->SyncLimitIs( UTIL_ToString( m_SyncLimit ) ) );
                                else
                                {
                                        m_SyncLimit = UTIL_ToUInt32( Payload );
 
                                        if( m_SyncLimit <= 10 )
                                        {
                                                m_SyncLimit = 10;
                                                SendAllChat( m_GHost->m_Language->SettingSyncLimitToMinimum( "10" ) );
                                        }
                                        else if( m_SyncLimit >= 10000 )
                                        {
                                                m_SyncLimit = 10000;
                                                SendAllChat( m_GHost->m_Language->SettingSyncLimitToMaximum( "10000" ) );
                                        }
                                        else
                                                SendAllChat( m_GHost->m_Language->SettingSyncLimitTo( UTIL_ToString( m_SyncLimit ) ) );
                                }
                        }
 
                        //
                        // !UNHOST
                        //
                        else if( Command == "unhost" && !m_CountDownStarted && Level >= 8 )
                                m_Exiting = true;
 
                        //
                        // !GAMEUNLOCK
                        //
                        else if( Command == "gameunlock" && Level == 10 )
                        {
                                SendAllChat( m_GHost->m_Language->GameUnlocked( ) );
                                m_Locked = false;
                        }
 
                        //
                        // !UNMUTE
                        //
                        else if( Command == "unmute" && Level >= 5 )
                        {
                                CGamePlayer *LastMatch = NULL;
                                uint32_t Matches = GetPlayerFromNamePartial( Payload, &LastMatch );
 
                                if( Matches == 0 )
                                        SendAllChat( m_GHost->m_Language->UnableToMuteNoMatchesFound( Payload ) );
                                else if( Matches == 1 )
                                {
                                        SendAllChat( m_GHost->m_Language->UnmutedPlayer( LastMatch->GetName( ), User ) );
                                        LastMatch->SetMuted( false );
                                }
                                else
                                        SendAllChat( m_GHost->m_Language->UnableToMuteFoundMoreThanOneMatch( Payload ) );
                        }
 
                        //
                        // !UNMUTEALL
                        //
                        else if( Command == "unmuteall" && m_GameLoaded && Level >= 5 )
                        {
                                SendAllChat( m_GHost->m_Language->GlobalChatUnmuted( ) );
                                m_MuteAll = false;
                        }
 
                        //
                        // !VIRTUALHOST
                        //
                        else if( Command == "virtualhost" && !Payload.empty( ) && Payload.size( ) <= 15 && !m_CountDownStarted && Level >= 9 )
                        {
                                DeleteVirtualHost( );
                                m_VirtualHostName = Payload;
                        }
 
                        //
                        // !VOTECANCEL
                        //
                        else if( Command == "votecancel" && !m_KickVotePlayer.empty( ) && Level >= 8 )
                        {
                                SendAllChat( m_GHost->m_Language->VoteKickCancelled( m_KickVotePlayer ) );
                                m_KickVotePlayer.clear( );
                                m_StartedKickVoteTime = 0;
                        }
 
                        //
                        // !W
                        //
                        else if( Command == "w" && !Payload.empty( ) && Level >= 9 )
                        {
                                // extract the name and the message
                                // e.g. "Varlock hello there!" -> name: "Varlock", message: "hello there!"
 
                                string Name;
                                string Message;
                                string :: size_type MessageStart = Payload.find( " " );
 
                                if( MessageStart != string :: npos )
                                {
                                        Name = Payload.substr( 0, MessageStart );
                                        Message = Payload.substr( MessageStart + 1 );
 
                                        for( vector<CBNET *> :: iterator i = m_GHost->m_BNETs.begin( ); i != m_GHost->m_BNETs.end( ); ++i )
                                                (*i)->QueueChatCommand( Message, Name, true );
                                }
 
                                HideCommand = true;
                        }
                }
                else
                {
                        CONSOLE_Print( "[GAME: " + m_GameName + "] admin command ignored, the game is locked" );
                        SendChat( player, m_GHost->m_Language->TheGameIsLocked( ) );
                }
        }
        else
        {
                if( !player->GetSpoofed( ) )
                        CONSOLE_Print( "[GAME: " + m_GameName + "] non-spoofchecked user [" + User + "] sent command [" + Command + "] with payload [" + Payload + "]" );
                else
                        CONSOLE_Print( "[GAME: " + m_GameName + "] non-admin [" + User + "] sent command [" + Command + "] with payload [" + Payload + "]" );
        }
 
        /*********************
        * NON ADMIN COMMANDS *
        *********************/
 
        //
        // !CHECKME
        //
 
        if( Command == "checkme" )
        {
                SendChat( player, "[" + User + "] (P: " + ( player->GetNumPings( ) > 0 ? UTIL_ToString( player->GetPing( m_GHost->m_LCPings ) ) + "ms" : "N/A" ) + ") (F: " + player->GetCLetter( ) + ") (Role: " + ( LevelName.empty() ? "unknown" : LevelName ) + ") (SpoofChecked: " + ( player->GetSpoofed( ) ? "Yes" : "No" ) + ") (Realm: " + ( player->GetSpoofedRealm( ).empty( ) ? "N/A" : player->GetSpoofedRealm( ) ) + ")" );
                if( player->GetKickVote( ) )
                        SendChat( player, "[INFO] You already voted to forfeit the game. See current results with '!wff'" );
                if( player->GetDrawVote( ) )
                         SendChat( player, "[INFO] You already voted to draw the game. You can change your mind by using '!undraw'" );
                string IgnoredPlayers;
                for( vector<string> :: iterator i = player->m_IgnoreList.begin( ); i != player->m_IgnoreList.end( ); ++i )
                {
                        if( IgnoredPlayers.empty( ) )
                                IgnoredPlayers = *i;
                        else
                                IgnoredPlayers = ", " + *i;
                }
                if( !IgnoredPlayers.empty( ) )
                        SendChat( player, "[INFO] Ignored players: "+IgnoredPlayers );
                if( player->GetAFKMarked( ) )
                        SendChat( player, "[WARNING] You got already marked as an AFKer, if you are AFK once again for more than 3 mins you will be kicked with an autoban." );
                if( player->GetFeedLevel( ) == 1 )
                         SendChat( player, "[WARNING] You are already marked as a feeder, might wanna be careful about dying." );
                if( player->GetFeedLevel( ) == 2 )
                        SendChat( player, "[WARNING] You are marked as a complete feeder, you could be votekicked." );
                if( player->GetHighPingTimes( ) > 0 )
                        SendChat( player, "[WARNING] You have been marked already ["+UTIL_ToString( player->GetHighPingTimes( ) )+"] as a high-ping player." );
        }
        //
        // !STATS
        //
 
        if( Command == "stats" && GetTime( ) - player->GetStatsSentTime( ) >= 5 )
        {
                string StatsUser = User;
 
                if( !Payload.empty( ) )
                        StatsUser = Payload;
                CGamePlayer *LastMatch = NULL;
                uint32_t Matches = GetPlayerFromNamePartial( StatsUser, &LastMatch );
                if( Matches == 0 )
                {
                        if( player->GetSpoofed( ) && Level >= 8 )
                                m_PairedGSChecks.push_back( PairedGSCheck( string( ), m_GHost->m_DB->ThreadedStatsPlayerSummaryCheck( StatsUser ) ) );
                        else
                                m_PairedGSChecks.push_back( PairedGSCheck( User, m_GHost->m_DB->ThreadedStatsPlayerSummaryCheck( StatsUser ) ) );
                }
                else if( Matches == 1 )
                {
                        if( player->GetSpoofed( ) && Level >= 8 )
                                m_PairedGSChecks.push_back( PairedGSCheck( string( ), m_GHost->m_DB->ThreadedStatsPlayerSummaryCheck( LastMatch->GetName( ) ) ) );
                        else
                                m_PairedGSChecks.push_back( PairedGSCheck( User, m_GHost->m_DB->ThreadedStatsPlayerSummaryCheck( LastMatch->GetName( ) ) ) );
                }
                else if( Matches > 1 )
                        SendChat( player, "Error, found to many name partial matching on ["+StatsUser+"]" );
 
                player->SetStatsSentTime( GetTime( ) );
        }
 
        //
        // !RANK
        //
 
        if( ( Command == "rank" || Command == "class" ) && GetTime( ) - player->GetStatsSentTime( ) >= 5 )
        {
                string StatsUser = User;
 
                if( !Payload.empty( ) )
                        StatsUser = Payload;
 
                CGamePlayer *LastMatch = NULL;
                uint32_t Matches = GetPlayerFromNamePartial( StatsUser, &LastMatch );
                if( Matches == 0 )
                {
                        if( player->GetSpoofed( ) && Level >= 8 )
                                m_PairedRankChecks.push_back( PairedRankCheck( string( ), m_GHost->m_DB->ThreadedStatsPlayerSummaryCheck( StatsUser ) ) );
                        else
                                m_PairedRankChecks.push_back( PairedRankCheck( User, m_GHost->m_DB->ThreadedStatsPlayerSummaryCheck( StatsUser ) ) );
                }
                else if( Matches == 1 )
                {
                        if( player->GetSpoofed( ) && Level >= 8 )
                                m_PairedRankChecks.push_back( PairedRankCheck( string( ), m_GHost->m_DB->ThreadedStatsPlayerSummaryCheck( LastMatch->GetName( ) ) ) );
                        else
                                m_PairedRankChecks.push_back( PairedRankCheck( User, m_GHost->m_DB->ThreadedStatsPlayerSummaryCheck( LastMatch->GetName( ) ) ) );
                }
                else if( Matches > 1 )
                        SendChat( player, "Error, found to many name partial matching on ["+StatsUser+"]" );
 
                player->SetStatsSentTime( GetTime( ) );
        }
 
        //
        // !STATSDOTA
        // !SD
        //
 
        else if( (Command == "statsdota" || Command == "sd") && GetTime( ) - player->GetStatsDotASentTime( ) >= 5 )
        {
                string StatsUser = User;
 
                if( !Payload.empty( ) )
                        StatsUser = Payload;
 
                CGamePlayer *LastMatch = NULL;
                uint32_t Matches = GetPlayerFromNamePartial( StatsUser, &LastMatch );
                if( Matches == 0 )
                {
                        if( player->GetSpoofed( ) && Level >= 8 )
                                m_PairedSChecks.push_back( PairedSCheck( string( ), m_GHost->m_DB->ThreadedStatsPlayerSummaryCheck( StatsUser ) ) );
                        else
                                m_PairedSChecks.push_back( PairedSCheck( User, m_GHost->m_DB->ThreadedStatsPlayerSummaryCheck( StatsUser ) ) );
                }
                else if( Matches == 1 )
                {
                        if( player->GetSpoofed( ) && Level >= 8 )
                                m_PairedSChecks.push_back( PairedSCheck( string( ), m_GHost->m_DB->ThreadedStatsPlayerSummaryCheck( LastMatch->GetName( ) ) ) );
                        else
                                m_PairedSChecks.push_back( PairedSCheck( User, m_GHost->m_DB->ThreadedStatsPlayerSummaryCheck( LastMatch->GetName( ) ) ) );
                }
                else if( Matches > 1 )
                        SendChat( player, "Error, found to many name partial matching on ["+StatsUser+"]" );
 
                player->SetStatsDotASentTime( GetTime( ) );
        }
 
        //
        // !VERSION
        //
 
        else if( Command == "version" )
        {
                if( player->GetSpoofed( ) && Level >= 8 )
                        SendAllChat( m_GHost->m_Language->VersionAdmin( m_GHost->m_Version ) );
                else
                        SendAllChat( m_GHost->m_Language->VersionNotAdmin( m_GHost->m_Version ) );
        }
 
        //
        // !WHOVOTEKICKED
        //
        else if( ( Command == "wvk" || Command == "whovk" || Command == "whovoted" || Command == "whovotekicked" ) && !m_KickVotePlayer.empty( ) )
        {
                SendChat( player, "Current votekick process for player ["+m_KickVotePlayer+"]" );
                SendChat( player, "Process is running since [" +UTIL_ToString(GetTime()-m_StartedKickVoteTime)+ "] seconds, [" +UTIL_ToString(60-(GetTime()-m_StartedKickVoteTime))+"] left to votekick" );
                uint32_t VotesNeeded = (float)( ( GetNumHumanPlayers( ) - 1 ) /2) * 0.75;
                uint32_t Votes = 0;
                string VotedPlayers;
                for( vector<CGamePlayer *> :: iterator i = m_Players.begin( ); i != m_Players.end( ); ++i )
                {
                        if( (*i)->GetKickVote( ) )
                        {
                                ++Votes;
                                if( !VotedPlayers.empty( ) )
                                        VotedPlayers+=", "+(*i)->GetName();
                                else
                                        VotedPlayers=(*i)->GetName();
                        }
                }
                SendChat( player, "["+UTIL_ToString(Votes)+"/"+UTIL_ToString(VotesNeeded)+"]: "+VotedPlayers );
        }
 
        //
        // !VOTEKICK
        //
 
        else if( Command == "votekick" && m_GHost->m_VoteKickAllowed && !Payload.empty( ) )
        {
                player->SetVKTimes( );
                if( player->GetVKTimes( ) == 8 )
                        m_PairedBanAdds.push_back( PairedBanAdd( User, m_GHost->m_DB->ThreadedBanAdd( player->GetJoinedRealm( ), player->GetName( ), player->GetExternalIPString( ), m_GameName, "PeaceMaker", "votekick abuse", 432000, "" ) ) );
                else if( player->GetVKTimes( ) == 5 )
                        m_Pairedpenps.push_back( Pairedpenp( string(), m_GHost->m_DB->Threadedpenp( player->GetName( ), "votekick abuse", "PeaceMaker", 1, "add" ) ) );
                else if( player->GetVKTimes( ) >= 2 )
                        SendChat( player, "[INFO] Abusive usage of the votekick command is banable." );
 
                if( !m_KickVotePlayer.empty( ) )
                        SendChat( player, m_GHost->m_Language->UnableToVoteKickAlreadyInProgress( ) );
                else if( m_Players.size( ) <= 3 )
                        SendChat( player, m_GHost->m_Language->UnableToVoteKickNotEnoughPlayers( ) );
                else
                {
                        string name;
                        string reason;
                        stringstream SS;
                        SS << Payload;
                        SS >> name;
                        SS >> reason;
                        if( SS.fail( ) || reason.empty( ) )
                        {
                                SendChat( player, "[INFO] Error. Please add a reason to votekick a player." );
                                return HideCommand;
                        }
                        if( !CustomVoteKickReason( reason ) )
                        {
                                SendChat( player, "[INFO] Error. Please add a valied reason to votekick, see the reasons with '!vkreasons'" );
                                return HideCommand;
                        }
                        else
                        {
                                SendChat( player, "[INFO] You used a correct votekick reason. We saved the reason and stats, abusive usage is banable." );
                                CGamePlayer *LastMatch = NULL;
                                uint32_t Matches = GetPlayerFromNamePartial( name, &LastMatch );
 
                                if( Matches == 0 )
                                        SendChat( player, m_GHost->m_Language->UnableToVoteKickNoMatchesFound( name ) );
                                else if( Matches == 1 )
                                {
                                        //see if the player is the only one left on his team
                                        unsigned char SID = GetSIDFromPID( LastMatch->GetPID( ) );
                                        bool OnlyPlayer = false;
 
                                        if( m_GameLoaded && SID < m_Slots.size( ) )
                                        {
                                                unsigned char Team = m_Slots[SID].GetTeam( );
                                                OnlyPlayer = true;
                                                char sid, team;
 
                                                for( vector<CGamePlayer *> :: iterator i = m_Players.begin( ); i != m_Players.end( ); i++)
                                                {
                                                        if( *i && LastMatch != *i && !(*i)->GetLeftMessageSent( ) )
                                                        {
                                                                sid = GetSIDFromPID( (*i)->GetPID( ) );
                                                                if( sid != 255 )
                                                                {
                                                                        team = m_Slots[sid].GetTeam( );
                                                                        if( team == Team )
                                                                        {
                                                                                OnlyPlayer = false;
                                                                                break;
                                                                        }
                                                                }
                                                        }
                                                }
                                        }
 
                                        uint32_t VLevel = 0;
                                        string VLevelName;
                                        for( vector<CBNET *> :: iterator i = m_GHost->m_BNETs.begin( ); i != m_GHost->m_BNETs.end( ); ++i )
                                        {
                                                if( (*i)->GetServer( ) == LastMatch->GetSpoofedRealm( ) )
                                                {
                                                        VLevel = (*i)->IsLevel( LastMatch->GetName( ) );
                                                        VLevelName = (*i)->GetLevelName( Level );
                                                        break;
                                                }
                                        }
 
                                        if( reason.find( "feeding" ) != string::npos && LastMatch->GetFeedLevel( ) == 0 )
                                        {
                                                SendChat( player, "[INFO] Player ["+LastMatch->GetName( )+"] wasnt marked as a feeder. Adding one additional infraction point for abusing the votekick command." );
                                                SendChat( player, "[INFO] Abuse points will be kept, and if you have to many this game, you will be banned." );
                                                player->SetVKTimes( );
                                                return HideCommand;
                                        }
 
                                        if( reason.find( "feeding" ) != string::npos && LastMatch->GetFeedLevel( ) == 1 )
                                        {
                                                SendChat( player, "[INFO] Player ["+LastMatch->GetName( )+"] is on a warning level, you may not votekick him yet." );
                                                SendChat( player, "[INFO] We will inform you when you are able to votekick a player for feeding." );
                                                return HideCommand;
                                        }
 
                                        if( VLevel > 2 && Level < VLevel )
                                                SendChat( player, "[INFO] You may not votekick this player." );
                                        else if( OnlyPlayer )
                                                SendChat( player, "[INFO] Unable to votekick player [" + LastMatch->GetName( ) + "] cannot votekick when there is only one player on the victim's team." );
                                        else if( LastMatch == player )
                                                SendChat( player, "[INFO] You cannot votekick yourself!" );
                                        else
                                        {
                                                m_KickVotePlayer = LastMatch->GetName( );
                                                m_StartedKickVoteTime = GetTime( );
 
                                                for( vector<CGamePlayer *> :: iterator i = m_Players.begin( ); i != m_Players.end( ); ++i )
                                                        (*i)->SetKickVote( false );
 
                                                player->SetKickVote( true );
                                                CONSOLE_Print( "[GAME: " + m_GameName + "] votekick against player [" + m_KickVotePlayer + "] started by player [" + User + "]" );
                                                SendAllChat( m_GHost->m_Language->StartedVoteKick( LastMatch->GetName( ), User, UTIL_ToString( (float)( ( GetNumHumanPlayers( ) - 1 ) /2) * 0.75, 0 ) ) );
                                                SendAllChat( m_GHost->m_Language->TypeYesToVote( string( 1, m_GHost->m_CommandTrigger ) ) );
                                        }
                                }
                                else
                                        SendChat( player, m_GHost->m_Language->UnableToVoteKickFoundMoreThanOneMatch( name ) );
                        }
                }
        }
 
        //
        // !VKREASONS
        //
        else if( Command == "vkreasons" && m_KickVotePlayer.empty( ) )
        {
                SendChat( player, "Custom VoteKick Reasons:" );
                SendChat( player, "Maphack, Fountainfarm, Feeding, Flaming & Gameruin" );
                return HideCommand;
        }
 
        //
        // !YES
        //
 
        else if( Command == "yes" && !m_KickVotePlayer.empty( ) && player->GetName( ) != m_KickVotePlayer && !player->GetKickVote( ) )
        {
                player->SetKickVote( true );
                uint32_t VotesNeeded = (float)( ( GetNumHumanPlayers( ) - 1 ) /2) * 0.75;
                uint32_t Votes = 0;
                uint32_t voteplayerteam;
 
                for( vector<CGamePlayer *> :: iterator i = m_Players.begin( ); i != m_Players.end( ); ++i )
                {
                        if( (*i)->GetName( ) == m_KickVotePlayer )
                                voteplayerteam = m_Slots[GetSIDFromPID( (*i)->GetPID( ) )].GetTeam( );
                }
                if( m_Slots[GetSIDFromPID( player->GetPID( ) )].GetTeam( ) == voteplayerteam )
                {
                        for( vector<CGamePlayer *> :: iterator i = m_Players.begin( ); i != m_Players.end( ); ++i )
                        {
                                if( (*i)->GetKickVote( ) )
                                        ++Votes;
                        }
 
                        if( Votes >= VotesNeeded )
                        {
                                CGamePlayer *Victim = GetPlayerFromName( m_KickVotePlayer, true );
 
                                if( Victim )
                                {
                                        Victim->SetDeleteMe( true );
                                        Victim->SetLeftReason( m_GHost->m_Language->WasKickedByVote( ) );
 
                                        if( !m_GameLoading && !m_GameLoaded )
                                                Victim->SetLeftCode( PLAYERLEAVE_LOBBY );
                                        else
                                                Victim->SetLeftCode( PLAYERLEAVE_LOST );
 
                                        if( !m_GameLoading && !m_GameLoaded )
                                                OpenSlot( GetSIDFromPID( Victim->GetPID( ) ), false );
 
                                        CONSOLE_Print( "[GAME: " + m_GameName + "] votekick against player [" + m_KickVotePlayer + "] passed with [" + UTIL_ToString( Votes ) + "/" + UTIL_ToString( VotesNeeded ) + "] votes." );
                                        SendAllChat( m_GHost->m_Language->VoteKickPassed( m_KickVotePlayer ) );
                                }
                                else
                                        SendAllChat( m_GHost->m_Language->ErrorVoteKickingPlayer( m_KickVotePlayer ) );
 
                                m_KickVotePlayer.clear( );
                                m_StartedKickVoteTime = 0;
                        }
                        else
                                SendAllChat( m_GHost->m_Language->VoteKickAcceptedNeedMoreVotes( m_KickVotePlayer, User, UTIL_ToString( VotesNeeded - Votes ) ) );
                }
                else
                        SendChat( player, "You may only vote for kicking players from your team." );
 
        }
 
        //
        // !DRAW
        //
        if( m_GameLoaded && ( Command == "draw" || Command == "undraw" ) && !m_SoftGameOver )
        {
                if( Command == "draw" )
                {
                        bool ChangedVote = true;
 
                        if( !player->GetDrawVote( ) )
                                player->SetDrawVote( true );
                        else
                                ChangedVote = false; //continue in case someone left and now we have enough votes
 
                        uint32_t VotesNeeded = (float)ceil( GetNumHumanPlayers( ) * 0.75 );
                        uint32_t Votes = 0;
 
                        for( vector<CGamePlayer *> :: iterator i = m_Players.begin( ); i != m_Players.end( ); i++)
                        {
                                if( (*i)->GetDrawVote( ) )
                                {
                                        Votes++;
                                }
                        }
 
                        if( Votes >= VotesNeeded )
                        {
                                SendAllChat( "The game has now been recorded as a draw. You may leave at any time." );
                                m_SoftGameOver = true;
                                m_GameOverTime = GetTime( );
                        }
                        else if( ChangedVote ) //only display message if they actually changed vote
                        {
                                SendAllChat( "Player [" + player->GetName( ) + "] has voted to draw the game. " + UTIL_ToString( VotesNeeded - Votes ) + " more votes are needed to pass the draw vote." );
                                SendChat( player, "Use !undraw to recall your vote to draw the game." );
                        }
                }
                else if( Command == "undraw" && player->GetDrawVote( ) )
                {
                        player->SetDrawVote( false );
                        SendAllChat( "[" + player->GetName( ) + "] recalled vote to draw the game." );
                }
        }
 
        //
        // !FORFEIT
        //
        if( m_GameLoaded && m_ForfeitTime == 0 && ( Command == "ff" || Command == "forfeit" ) && !m_SoftGameOver )
        {
                if( GetTime( ) - m_GameLoadedTime <= ( m_GHost->m_MinFF*60 - 200*m_Leavers ) )
                        SendChat( player, "[INFO] You may FF after [20] minutes, ["+UTIL_ToString( m_GHost->m_MinFF - m_Leavers*2 - ( GetTime( ) - m_GameLoadedTime ) )+"] minutes remaining." );
                else
                {
                        bool ChangedVote = true;
 
                        if( !player->GetForfeitVote( ) )
                                player->SetForfeitVote( true );
                        else
                                ChangedVote = false;
 
                        char playerSID = GetSIDFromPID( player->GetPID( ) );
 
                        if( playerSID != 255 )
                        {
                                char playerTeam = m_Slots[playerSID].GetTeam( );
 
                                // whether or not all players on the team of the player who typed the command forfeited
                                bool AllVoted = true;
                                int numVoted = 0;
                                int numTotal = 0;
 
                                for( vector<CGamePlayer *> :: iterator i = m_Players.begin( ); i != m_Players.end( ); i++)
                                {
                                        if( *i && !(*i)->GetLeftMessageSent( ) )
                                        {
                                                char sid = GetSIDFromPID( (*i)->GetPID( ) );
 
                                                if( sid != 255 && m_Slots[sid].GetTeam( ) == playerTeam )
                                                {
                                                        numTotal++;
 
                                                        if( !(*i)->GetForfeitVote( ) )
                                                                AllVoted = false;
                                                        else
                                                                numVoted++;
                                                }
                                        }
                                }
 
                                m_ForfeitTeam = playerTeam;
 
                                // observers cannot forfeit!
                                if( m_ForfeitTeam == 0 || m_ForfeitTeam == 1 )
                                {
                                        string ForfeitTeamString = "Sentinel";
                                        if( m_ForfeitTeam == 1 ) ForfeitTeamString = "Scourge";
 
                                        if( AllVoted )
                                        {
                                                m_Stats->SetWinner( ( playerTeam + 1 ) % 2 );
                                                m_ForfeitTime = GetTime( );
                                                SendAllChat( "The [" + ForfeitTeamString + "] forfeited the game." );
                                                SendAllChat( "Please stay until the gameover timer has finished to keep your stats!" );
                                        }
 
                                        else if( ChangedVote )
                                        {
                                                SendAllChat( "[" + player->GetName( ) + "] voted to forfeit the game." );
                                                SendAllChat( "[" + ForfeitTeamString + "] forfeit status: [" + UTIL_ToString( numVoted ) + "/" + UTIL_ToString( numTotal ) + "]" );
                                        }
                                }
                        }
                }
        }
 
        /**********************
        * GRIEF-CODE COMMANDS *
        **********************/
 
        //
        // !WFF !WHOFF
        //
        else if( Command == "whoff" || Command == "wff" )
        {
                int Votes=0;
                int SeVotes=0;
                int ScVotes=0;
                int SePlayers=0;
                int ScPlayers=0;
                string SeNames;
                string ScNames;
                bool twoteammap = true;
                for( vector<CGamePlayer *> :: iterator i = m_Players.begin( ); i != m_Players.end( ); i++)
                {
                        char sid = GetSIDFromPID( (*i)->GetPID( ) );
                        if (sid <= 4 && sid >= 0)
                        {
                          if( (*i)->GetForfeitVote( ))
                          {
                                if( !SeNames.empty() )
                                        SeNames += ", "+(*i)->GetName();
                                else
                                        SeNames = (*i)->GetName();
                                SeVotes++;
                          }
                          SePlayers++;
                        }
                        else if(sid >= 5 && sid <= 9)
                        {
                         if( (*i)->GetForfeitVote( ))
                          {
 
                                if( !ScNames.empty() )
                                        ScNames += ", "+(*i)->GetName();
                                else
                                        ScNames = (*i)->GetName();
                                ScVotes++;
                          }
                          ScPlayers++;
                        }
                        else if( m_Slots[sid].GetTeam( ) != 12 )
                                twoteammap = false;
                        Votes++;
                }
                if( Votes == 0 )
                        SendChat( player, "No one has voted for forfeiting the game yet" );
                else if( !twoteammap )
                        SendChat( player, "Error, this is not a two-team map, you cannot forfeit here" );
                else
                {
                        SendChat( player, "Forfeits:" );
                        SendChat( player, "[Sentinel ("+UTIL_ToString(SeVotes)+"/"+UTIL_ToString(SePlayers)+"): "+SeNames );
                        SendChat( player, "[Scourge ("+UTIL_ToString(ScVotes)+"/"+UTIL_ToString(ScPlayers)+"): "+ScNames );
                }
        }
 
        //
        // !OB
        //
        else if( Command == "ob" || Command == "obs" || Command == "observe" )
        {
                if( Level > 2 )
                {
                        if( m_Slots[11].GetSlotStatus( ) != SLOTSTATUS_OCCUPIED )
                        {
                                unsigned char oldsid = GetSIDFromPID( player->GetPID( ) );
                                SwapSlots( oldsid, 11 );
                                OpenSlot( oldsid, true );
                                m_AutoStartPlayers = 11;
                                SendAllChat( "Player [" + player->GetName( ) + "] will observe the game." );
                                SendAllChat( "Set autostart automatically to 11 players." );
                        }
                        else
                                SendChat( player, "Error. There is already a game observer." );
                }
                else
                        SendChat( player, "Error. You require at least to be a safelisted member to have access to this command." );
        }
 
        //
        // !UNOB
        //
        else if( Command == "unob" || Command == "ubobs" || Command == "unobserve" )
        {
                unsigned char SID = GetSIDFromPID( player->GetPID( ) );
                if( Level > 2 && SID == 11 )
                {
                        int32_t newslot = -1;
                        int c = 0;
                        for( vector<CGameSlot> :: iterator i = m_Slots.begin( ); i != m_Slots.end( ); ++i )
                        {
                                if( (*i).GetSlotStatus( ) == SLOTSTATUS_OPEN )
                                {
                                        newslot = c;
                                        break;
                                }
                                c++;
                        }
 
                        if( newslot == -1 )
                                newslot = m_LatestSlot;
 
                        SwapSlots( newslot, 11 );
                        CloseSlot( 11, true );
                        m_AutoStartPlayers = 10;
                        SendAllChat( "Player [" + player->GetName( ) + "] will no longer observe the game." );
                        SendAllChat( "Set autostart automatically to 10 players." );
                }
                else
                        SendChat( player, "Error. You require at least to be a safelisted member to have access to this command." );
        }
 
        //
        // !PW  !PASS   !PASSWORD
        //
 
        else if( Command == "pw" || Command == "pass" || Command == "password" )
        {
                string Status;
                string Password;
                stringstream SS;
                SS << Payload;
                SS >> Password;
                if( !SS.eof( ) )
                {
                        getline( SS, Status );
                        string :: size_type Start = Status.find_first_not_of( " " );
 
                        if( Start != string :: npos )
                                Status = Status.substr( Start );
                }
 
                if( Status.empty() )
                {
                        if( player->GetPasswordProt( ) )
                        {
                                m_PairedPassChecks.push_back( PairedPassCheck( User, m_GHost->m_DB->ThreadedPassCheck( User, Password, 0 ) ) );
                        }
                }
                else if( Status == "0" || Status == "clear" )
                        m_PairedPassChecks.push_back( PairedPassCheck( User, m_GHost->m_DB->ThreadedPassCheck( User, Password, 1 ) ) );
                else
                        SendChat( player, "Error wrong status, please use 'clear' or '0' to remove the password protection" );
 
                HideCommand = true;
        }
 
        //
        // !I   !INBOX
        //
        else if( ( Command == "i" || Command == "inbox" ) && Payload.empty( ) )
                m_PairedINChecks.push_back( PairedINCheck( User, m_GHost->m_DB->ThreadedInboxSummaryCheck( User ) ) );
 
        //
        // !PM
        //
        else if( Command == "pm" && !Payload.empty( ) )
        {
                string UserTo;
                string Message;
                stringstream SS;
                SS << Payload;
                SS >> UserTo;
                if( !SS.eof( ) )
                {
                        getline( SS, Message );
                        string :: size_type Start = Message.find_first_not_of( " " );
 
                        if( Start != string :: npos )
                                Message = Message.substr( Start );
                }
                else
                        SendChat( player, "Error. Wrong input, please use '!pm <user> <message>'" );
 
                if( UserTo.length() >= 3 )
                {
                        if( Payload.length() > 3 )
                        {
                                m_Pairedpms.push_back( Pairedpm( User, m_GHost->m_DB->Threadedpm( User, UserTo, 0, Message, "add" ) ) );
                        }
                        else
                                SendChat( player, "Error. The message is to short." );
                }
                else
                        SendChat( player, "Error. This User is invalid" );
        }
 
        //
        // !STREAK
        //
        else if( Command == "streak" )
        {
                string StatsUser = User;
 
                if( !Payload.empty( ) )
                        StatsUser = Payload;
 
                // check for potential abuse
 
                if( !StatsUser.empty( ) && StatsUser.size( ) < 16 && StatsUser[0] != '/' )
                        m_PairedStreakChecks.push_back( PairedStreakCheck( User, m_GHost->m_DB->ThreadedStatsPlayerSummaryCheck( StatsUser ) ) );
        }
 
        //
        // !POINTS      !P
        //
        else if( Command == "points" || Command == "p" || Command == "pts" )
        {
                string StatsUser = User;
 
                if( !Payload.empty( ) )
                        StatsUser = Payload;
 
                // check for potential abuse
 
                if( !StatsUser.empty( ) && StatsUser.size( ) < 16 && StatsUser[0] != '/' )
                        m_PairedSSs.push_back( PairedSS( User, m_GHost->m_DB->ThreadedStatsSystem( StatsUser, "betsystem", 0, "betcheck" ) ) );
        }
 
        //
        // !BET !B
        //
        else if( ( Command == "bet" || Command == "b" ) && m_GameLoaded && !m_GameLoading )
        {
                if( !Payload.find_first_not_of( "1234567890" ) == string :: npos )
                {
                        SendChat( player, "Error. You should bet an amount." );
                        return HideCommand;
                }
                if( UTIL_ToUInt32( Payload ) > 50 || UTIL_ToUInt32( Payload ) <= 0 )
                {
                        SendChat( player, "Error. You shouldn't bet an amount over 50 and not lower than 0 or 0." );
                        return HideCommand;
                }
                if( UTIL_ToUInt32( Payload ) < 0 )
                {
                        SendChat( player, "Error. You shouldn't bet a negative amount." );
                        return HideCommand;
                }
                if( GetTime() - m_GameLoadedTime >= 300 )
                {
                        SendChat( player, "Error. You may should not bet yet, its already too late" );
                        return HideCommand;
                }
                else
                        m_PairedSSs.push_back( PairedSS( User, m_GHost->m_DB->ThreadedStatsSystem( User, "betsystem", UTIL_ToUInt32( Payload ), "bet" ) ) );
        }
 
        //
        // !PAUSE
        //
        else if( Command == "pause" && !m_GameLoading && m_GameLoaded && !player->GetUsedPause() )
        {
                if( Level > 2 )
                {
                        if( !m_PauseReq )
                        {
                                SendAllChat( "User [" + player->GetName() + "] requested to pause the game for 60 seconds" );
                                SendAllChat( "Game will be paused in 10 seconds." );
                                player->SetUsedPause( true );
                                m_PauseReq = true;
                                m_PauseIntroTime = GetTime();
                                m_LastCountDownTicks = 0;
                                m_Paused = false;
                        }
                        else
                                SendChat( player, "Error, someone already requested to pause the game." );
                }
                else
                        SendChat( player, "Error, you need to be at least a safelisted player to request a pause." );
        }
 
        //
        // !UNPAUSE
        //
        else if( Command == "unpause" && !m_GameLoading && m_GameLoaded && !player->GetUsedPause() )
        {
                if( Level > 2 )
                {
                        if( m_Paused )
                        {
                                SendAllChat( "User [" + player->GetName() + "] requested to unpause the game." );
                                m_PauseTime = 55;
                                m_PauseTicks = 5;
                                m_LastCountDownTicks = 0;
                        }
                        else
                                SendChat( player, "Error, the game isn't pause." );
                }
                else
                        SendChat( player, "Error, you need to be at least a safelisted player to request a pause." );
        }
 
        //
        // !WP  !WINPERC        !CB     !CHECKBALANCE
        //
        else if( ( Command == "wp" || Command == "cb" || Command == "winperc" || Command == "checkbalance" ) && Payload.empty( ) )
        {
                m_ScourgeWinPoints = 0.0;
                m_SentinelWinPoints = 0.0;
                m_TotalWinPoints = 0.0;
                bool twoteam = true;
                for( vector<CGamePlayer *> :: iterator i = m_Players.begin( ); i != m_Players.end( ); ++i )
                {
                        char sid = GetSIDFromPID( (*i)->GetPID( ) );
                        if (sid <= 4 && sid >= 0)
                        {
                                int Win = 0;
                                if( (*i)->GetGames( ) >= 10 )
                                        Win = ((*i)->GetWinPerc( ) * (*i)->GetGames( ));
                                else if( Win == 0 )
                                        Win = (33 * (*i)->GetGames( ));
                                else if( (*i)->GetGames( ) == 0 )
                                        Win = 33;
 
                                m_SentinelWinPoints += Win;
                                m_TotalWinPoints += Win;
                        }
                        else if(sid >= 5 && sid <= 9)
                        {
                                int Win = 0;
                                if( (*i)->GetGames( ) >= 10 )
                                        Win = ((*i)->GetWinPerc( ) * (*i)->GetGames( ));
                                else if( Win == 0 )
                                        Win = (33 * (*i)->GetGames( ));
                                else if( (*i)->GetGames( ) == 0 )
                                        Win = 33;
 
                                m_ScourgeWinPoints += Win;
                                m_TotalWinPoints += Win;
                        }
                        else if( m_Slots[sid].GetTeam( ) != 12 )
                                twoteam = false;
 
                }
                if( twoteam )
                {
                        if( m_TotalWinPoints != 0 )
                        {
                                string SeWP = UTIL_ToString( ( m_SentinelWinPoints / m_TotalWinPoints ) * 100, 1);
                                string ScWP = UTIL_ToString( ( m_ScourgeWinPoints / m_TotalWinPoints ) * 100, 1);
                                SendAllChat( "Win Chance [Sentinel: " + SeWP + "%] [Scourge: " + ScWP + "%]" );
                        }
                        else
                                SendChat( player, "Error. Currently one team hasn't got a player recorded" );
                }
                else
                        SendChat( player, "Error, this isn't a two-team map" );
        }
 
        //
        // !SLAP
        //
 
        else if( Command == "slap" && !Payload.empty( ) )
        {
                CGamePlayer *LastMatch = NULL;
                uint32_t Matches = GetPlayerFromNamePartial( Payload , &LastMatch );
 
                if ( Matches !=1 )
                        return HideCommand;
 
                uint32_t VLevel = 0;
                string VLevelName;
                for( vector<CBNET *> :: iterator i = m_GHost->m_BNETs.begin( ); i != m_GHost->m_BNETs.end( ); ++i )
                {
                        if( (*i)->GetServer( ) == LastMatch->GetSpoofedRealm( ) )
                        {
                                VLevel = (*i)->IsLevel( LastMatch->GetName( ) );
                                VLevelName = (*i)->GetLevelName( Level );
                                break;
                        }
                }
 
                if( VLevel > Level )
                {
                        SendChat( player->GetPID( ), "You can't slap a " + VLevelName );
                        return HideCommand;
                }
 
                srand( (unsigned)time( NULL ) );
                int RandomNumber = ( rand( ) % 8 );
 
                if ( LastMatch->GetName( ) != User )
                {
                        if ( RandomNumber == 0 )
                                SendAllChat( User + " slaps " + LastMatch->GetName( ) + " with a large trout." );
                        else if ( RandomNumber == 1 )
                                SendAllChat( User + " slaps " + LastMatch->GetName( ) + " with a pink Macintosh." );
                        else if ( RandomNumber == 2 )
                                SendAllChat( User + " throws a Playstation 3 at " + LastMatch->GetName( ) + "." );
                        else if ( RandomNumber == 3 )
                                SendAllChat( User + " drives a car over " + LastMatch->GetName( ) + "." );
                        else if ( RandomNumber == 4 )
                        {
                                SendAllChat( User + " tries to steals " + LastMatch->GetName( ) + "'s cookies." );
                                if( LastMatch->GetCookies() != 0 )
                                {
                                        player->SetCookie( LastMatch->GetCookies( ) );
                                        LastMatch->SetCookie( 0 );
                                        SendChat( player, "You can now eat cookies by using '!eat'" );
                                        SendAllChat( LastMatch->GetName( ) + " had "+ UTIL_ToString( LastMatch->GetCookies( ) ) + " cookie(s)." );
                                }
                                else
                                        SendAllChat( "But " + LastMatch->GetName( ) + " hadn't any cookies :( " );
                        }
                        else if ( RandomNumber == 5 )
                                SendAllChat( User + " washes " + LastMatch->GetName( ) + "'s car.  Oh, the irony!" );
                        else if ( RandomNumber == 6 )
                                SendAllChat( User + " burns " + LastMatch->GetName( ) + "'s house." );
                        else if ( RandomNumber == 7 )
                                SendAllChat( User + " finds " + LastMatch->GetName( ) + "'s picture on uglypeople.com." );
                }
                else
                {
                        if ( RandomNumber == 0 )
                                SendAllChat( User + " slaps himself with a large trout." );
                        else if ( RandomNumber == 1 )
                                SendAllChat( User + " slaps himself with a pink Macintosh." );
                        else if ( RandomNumber == 2 )
                                SendAllChat( User + " throws a Playstation 3 at himself." );
                        else if ( RandomNumber == 3 )
                                SendAllChat( User + " drives a car over himself." );
                        else if ( RandomNumber == 4 )
                                SendAllChat( User + " steals his cookies. mwahahah!" );
                        else if ( RandomNumber == 5 )
                                SendAllChat( User + " searches yahoo.com for goatsex + " + User + ". " + UTIL_ToString( rand( ) ) + " hits WEEE!" );
                        else if ( RandomNumber == 6 )
                                SendAllChat( User + " burns his house." );
                        else if ( RandomNumber == 7 )
                                SendAllChat( User + " finds his picture on uglypeople.com." );
                }
        }
 
        //
        // !EAT
        //
        else if( Command == "eat" && Payload.empty( ) )
        {
                if( player->GetCookies( ) != 0 )
                {
                        player->SetCookie( player->GetCookies( )-1 );
                        SendAllChat( player->GetName( ) + " ate a cookie, that was tasty!" );
                        SendChat( player, "You have now " + UTIL_ToString( player->GetCookies( ) ) + " cookies left." );
                }
                else
                        SendChat( player, "Error, you dont have a cookie!" );
        }
 
        //
        // !IGNORE
        //
        else if( Command == "ignore" && !Payload.empty() )
        {
                CGamePlayer *LastMatch = NULL;
                uint32_t Matches = GetPlayerFromNamePartial( Payload, &LastMatch );
 
                if( Matches == 0 )
                        SendChat( player, "Unable to ignore player [" + Payload + "]. No matches found." );
                else if( Matches == 1 )
                {
                        player->Ignore( LastMatch->GetName( ) );
                        SendChat( player, "You have ignored player [" + LastMatch->GetName( ) + "]. You will not be able to send or receive messages from the player." );
                }
                else
                        SendChat( player, "Unable to ignore player [" + Payload + "]. Found more than one match." );
        }
 
        //
        // !UNIGNORE
        //
        else if( Command == "unignore" && !Payload.empty() )
        {
                CGamePlayer *LastMatch = NULL;
                uint32_t Matches = GetPlayerFromNamePartial( Payload, &LastMatch );
 
                if( Matches == 0 )
                        SendChat( player, "Unable to unignore player [" + Payload + "]. No matches found." );
                else if( Matches == 1 )
                {
                        player->UnIgnore( LastMatch->GetName( ) );
                        SendChat( player, "You have unignored player [" + LastMatch->GetName( ) + "]." );
                }
                else
                        SendChat( player, "Unable to unignore player [" + Payload + "]. Found more than one match." );
        }
 
        //
        // !IGNOREALL
        //
        else if( Command == "ignoreall" )
        {
                for( vector<CGamePlayer *> :: iterator i = m_Players.begin( ); i != m_Players.end( ); ++i )
                {
                        player->Ignore( (*i)->GetName( ) );
                }
                SendChat( player, "You are know ignoring all players." );
        }
 
        //
        // !UNIGNOREALL
        //
        else if( Command == "unignoreall" )
        {
                for( vector<CGamePlayer *> :: iterator i = m_Players.begin( ); i != m_Players.end( ); ++i )
                {
                        player->UnIgnore( (*i)->GetName( ) );
                }
                SendChat( player, "You are know ignoring all players." );
        }
 
        //
        // !RULES
        //
        else if( Command == "r" || Command == "rules" )
        {
                if( Payload.empty( ) )
                {
                        SendChat( player, "You need to specify a correct reason." );
                        SendChat( player, "Reasons: Leaving|Flaming|AFK|Spam|Fountainfarm|Gameruin|Hacking|Bugabuse" );
                }
                transform( Payload.begin( ), Payload.end( ), Payload.begin( ), (int(*)(int))tolower );
                if( Payload == "leave" || Payload == "leaving" )
                        SendChat( player, "[Leaving] Leave the game more than 3 min. before tree/throne is dead." );
                else if( Payload == "flame" || Payload == "flameing" )
                        SendChat( player, "[Flaming] Insult other people, using words like 'noob', 'fuck you', 'bastard', 'motherfucker', and so on. It is admins decision which words count as flaming!." );
                else if( Payload == "afk" )
                        SendChat( player, "[AFK] Being afk and/or not playing any more. Also moving around at fountain / in base. It is admins decision what counts as afk!." );
                else if( Payload == "spam" || Payload == "spaming" )
                        SendChat( player, "[Spam] Chatting very much text in very less time will be muted and banned." );
                else if( Payload == "fountainfarm" )
                        SendChat( player, "[FountainFarm] Kill enemies inside the fountain area (healing area). Does not invlude Zeus ulti. At the end, its admins decision what counts as fountain farm." );
                else if( Payload == "gameruin" )
                {
                        SendChat( player, "[Game Ruin] Ruining the fun for other players in any way, inclusive but not exclusive item stealing, item destruction, feed on purpose, buying mass courier," );
                        SendChat( player, "[Game Ruin] Give enemies information about team, ... At the end, its admins decision what counts as game ruining." );
                }
                else if( Payload == "hack" || Payload == "hacking" )
                        SendChat( player, "[Hack] Using tools like maphack (or any other hacking tools) or abusing known bugs of the map. At the end, its admins decision what counts as bug abusing." );
                else
                {
                        SendChat( player, "Error, you picked a false category, use one of the following:" );
                        SendChat( player, "Reasons: Leaving|Flaming|AFK|Spam|FountainFarm|GameRuin|Hacking|BugAbuse" );
                }
        }
 
/*
        //
        // !LOCK
        //
        else if( ( Command == "lock" || Command == "l" ) && Payload.empty( ) )
        {
         if( Level > 2 )
         {
                if( !player->GetLocked( ) )
                {
                        if( m_LockedPlayers >= 4 )
                                SendChat( player, "Error. There already to many locked players." );
                        else
                        {
                                SendAllChat( "Player [" + User + "] is now locked." );
                                player->SetLocked( true );
                                m_LockedPlayers++;
                        }
                }
                else
                        SendChat( player, "Error. You are already locked." );
         }
         else
                SendChat( player, "Error. You require a reserved slot to be able to use this command." );
        }
 
        //
        // !UNLOCK
        //
        else if( ( Command == "unlock" || Command == "ul" ) && Payload.empty( ) )
        {
         if( Level > 2 )
         {
                if( player->GetLocked( ) )
                {
                        SendAllChat( "Player [" + User + "] is now unlocked." );
                        player->SetLocked( false );
                        m_LockedPlayers--;
                }
                else
                        SendChat( player, "Error. You are not locked." );
         }
         else
                SendChat( player, "Error. You require a reserved slot to be able to use this command." );
        }
*/
        return HideCommand;
}
 
void CGame :: EventGameStarted( )
{
        CBaseGame :: EventGameStarted( );
 
        // record everything we need to ban each player in case we decide to do so later
        // this is because when a player leaves the game an admin might want to ban that player
        // but since the player has already left the game we don't have access to their information anymore
        // so we create a "potential ban" for each player and only store it in the database if requested to by an admin
 
        for( vector<CGamePlayer *> :: iterator i = m_Players.begin( ); i != m_Players.end( ); ++i )
                m_DBBans.push_back( new CDBBan( (*i)->GetJoinedRealm( ), (*i)->GetName( ), (*i)->GetExternalIPString( ), string( ), string( ), string( ), string( ), string(), string(), string(), string(), string() ) );
}
 
bool CGame :: IsGameDataSaved( )
{
        return m_CallableGameAdd && m_CallableGameAdd->GetReady( );
}
 
void CGame :: SaveGameData( )
{
        CONSOLE_Print( "[GAME: " + m_GameName + "] saving game data to database" );
        m_CallableGameAdd = m_GHost->m_DB->ThreadedGameAdd( m_GHost->m_BNETs.size( ) == 1 ? m_GHost->m_BNETs[0]->GetServer( ) : string( ), m_DBGame->GetMap( ), m_GameName, m_OwnerName, m_GameTicks / 1000, m_GameState, m_CreatorName, m_CreatorServer, m_GameType, m_LobbyLog, m_GameLog );
        m_GHost->m_FinishedGames++;
        m_GHost->m_CheckForFinishedGames = GetTime();
}
 
bool CGame :: IsAutoBanned( string name )
{
        for( vector<string> :: iterator i = m_AutoBans.begin( ); i != m_AutoBans.end( ); i++ )
        {
                if( *i == name )
                        return true;
        }
 
        return false;
}
 
bool CGame :: CustomVoteKickReason( string reason )
{
        transform( reason.begin( ), reason.end( ), reason.begin( ), (int(*)(int))tolower );
        //Votekick reasons: maphack, fountainfarm, feeding, flaming, game ruin
        if( reason.find( "maphack" ) != string::npos || reason.find( "fountainfarm" ) != string::npos || reason.find( "feeding" ) != string::npos || reason.find( "flaming" ) != string::npos || reason.find( "gameruin" ) != string::npos )
                return true;
 
        return false;
}