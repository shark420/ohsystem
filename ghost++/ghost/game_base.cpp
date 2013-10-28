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
#include "replay.h"
#include "gameplayer.h"
#include "gameprotocol.h"
#include "game_base.h"
#include "gcbiprotocol.h"

#include <cmath>
#include <string.h>
#include <time.h>

#include "next_combination.h"

//
// CBaseGame
//

CBaseGame :: CBaseGame( CGHost *nGHost, CMap *nMap, CSaveGame *nSaveGame, uint16_t nHostPort, unsigned char nGameState, string nGameName, string nOwnerName, string nCreatorName, string nCreatorServer, uint32_t nGameType ) : m_GHost( nGHost ), m_SaveGame( nSaveGame ), m_Replay( NULL ), m_Exiting( false ), m_Saving( false ), m_HostPort( nHostPort ), m_GameState( nGameState ), m_VirtualHostPID( 255 ), m_FakePlayerPID( 255 ), m_GProxyEmptyActions( 0 ), m_GameName( nGameName ), m_LastGameName( nGameName ), m_VirtualHostName( m_GHost->m_VirtualHostName ), m_OwnerName( nOwnerName ), m_CreatorName( nCreatorName ), m_CreatorServer( nCreatorServer ), m_GameType( nGameType), m_HCLCommandString( nMap->GetMapDefaultHCL( ) ), m_RandomSeed( GetTicks( ) ), m_EntryKey( rand( ) ), m_Latency( m_GHost->m_Latency ), m_SyncLimit( m_GHost->m_SyncLimit ), m_SyncCounter( 0 ), m_GameTicks( 0 ), m_CreationTime( GetTime( ) ), m_LastPingTime( GetTime( ) ), m_LastRefreshTime( GetTime( ) ), m_LastDownloadTicks( GetTime( ) ), m_DownloadCounter( 0 ), m_LastDownloadCounterResetTicks( GetTime( ) ), m_LastAnnounceTime( 0 ), m_AnnounceInterval( 0 ), m_LastAutoStartTime( GetTime( ) ), m_AutoStartPlayers( 0 ), m_LastCountDownTicks( 0 ), m_CountDownCounter( 0 ), m_StartedLoadingTicks( 0 ), m_StartPlayers( 0 ), m_LastLagScreenResetTime( 0 ), m_LastActionSentTicks( 0 ), m_LastActionLateBy( 0 ), m_StartedLaggingTime( 0 ), m_LastLagScreenTime( 0 ), m_LastReservedSeen( GetTime( ) ), m_StartedKickVoteTime( 0 ), m_GameOverTime( 0 ), m_LastPlayerLeaveTicks( 0 ), m_MinimumScore( 0. ), m_MaximumScore( 0. ), m_SlotInfoChanged( false ), m_Locked( false ), m_RefreshMessages( m_GHost->m_RefreshMessages ), m_RefreshError( false ), m_RefreshRehosted( false ), m_MuteAll( false ), m_MuteLobby( false ), m_CountDownStarted( false ), m_GameLoading( false ), m_GameLoaded( false ), m_LoadInGame( nMap->GetMapLoadInGame( ) ), m_Lagging( false ), m_AutoSave( m_GHost->m_AutoSave ), m_MatchMaking( false ), m_LocalAdminMessages( m_GHost->m_LocalAdminMessages ), m_PauseReq( false ), m_PauseTicks ( 5 ), m_SendPauseInfo( false ), m_GameNoGarena( false ), m_LastInGameAnnounce( 0 )
{
	m_GHost->m_HostCounter++;
	m_GHost->SaveHostCounter();
	m_HostCounter = m_GHost->m_HostCounter;
	m_Socket = new CTCPServer( );
	m_Protocol = new CGameProtocol( m_GHost );
	m_Map = new CMap( *nMap );
	uint32_t m_DatabaseID;                          // the ID number from the database, which we'll use to save replay
	m_LockedPlayers = 0;
	m_LogData = string();
	m_AdminLog.clear();
	m_Denied.clear();
	m_LastLogDataUpdate = GetTime();
        m_ChatID = m_HostCounter-1;
	m_PlayerUpdate = false;
	m_Balanced = false;
	m_LastProcessedTicks = 0;
	m_SoftGameOver = false;
	m_SendAnnounce = false;
	m_LimitedCountries.clear();
	m_LimitCountries = false;
	m_DenieCountries = false;
	m_SendGameLoaded = false;
	m_LastPingWarn = GetTime( );
	m_ModeVoted = false;
	m_Leavers = 0;

	if( m_GHost->m_SaveReplays && !m_SaveGame )
		m_Replay = new CReplay( );

	// wait time of 1 minute  = 0 empty actions required
	// wait time of 2 minutes = 1 empty action required
	// etc...

	if( m_GHost->m_ReconnectWaitTime != 0 )
	{
		m_GProxyEmptyActions = m_GHost->m_ReconnectWaitTime - 1;

		// clamp to 9 empty actions (10 minutes)

		if( m_GProxyEmptyActions > 9 )
			m_GProxyEmptyActions = 9;
	}

	if( m_SaveGame )
	{
		m_EnforceSlots = m_SaveGame->GetSlots( );
		m_Slots = m_SaveGame->GetSlots( );

		// the savegame slots contain player entries
		// we really just want the open/closed/computer entries
		// so open all the player slots

                for( vector<CGameSlot> :: iterator i = m_Slots.begin( ); i != m_Slots.end( ); ++i )
		{
			if( (*i).GetSlotStatus( ) == SLOTSTATUS_OCCUPIED && (*i).GetComputer( ) == 0 )
			{
				(*i).SetPID( 0 );
				(*i).SetDownloadStatus( 255 );
				(*i).SetSlotStatus( SLOTSTATUS_OPEN );
			}
		}
	}
	else
		m_Slots = m_Map->GetSlots( );

	if( !m_GHost->m_IPBlackListFile.empty( ) )
	{
		ifstream in;
		in.open( m_GHost->m_IPBlackListFile.c_str( ) );

		if( in.fail( ) )
			CONSOLE_Print( "[GAME: " + m_GameName + "] error loading IP blacklist file [" + m_GHost->m_IPBlackListFile + "]" );
		else
		{
			CONSOLE_Print( "[GAME: " + m_GameName + "] loading IP blacklist file [" + m_GHost->m_IPBlackListFile + "]" );
			string Line;

			while( !in.eof( ) )
			{
				getline( in, Line );

				// ignore blank lines and comments

				if( Line.empty( ) || Line[0] == '#' )
					continue;

				// remove newlines and partial newlines to help fix issues with Windows formatted files on Linux systems

				Line.erase( remove( Line.begin( ), Line.end( ), ' ' ), Line.end( ) );
				Line.erase( remove( Line.begin( ), Line.end( ), '\r' ), Line.end( ) );
				Line.erase( remove( Line.begin( ), Line.end( ), '\n' ), Line.end( ) );

				// ignore lines that don't look like IP addresses

				if( Line.find_first_not_of( "1234567890." ) != string :: npos )
					continue;

				m_IPBlackList.insert( Line );
			}

			in.close( );

			CONSOLE_Print( "[GAME: " + m_GameName + "] loaded " + UTIL_ToString( m_IPBlackList.size( ) ) + " lines from IP blacklist file" );
		}
	}

	// start listening for connections

	if( !m_GHost->m_BindAddress.empty( ) )
		CONSOLE_Print( "[GAME: " + m_GameName + "] attempting to bind to address [" + m_GHost->m_BindAddress + "]" );
	else
		CONSOLE_Print( "[GAME: " + m_GameName + "] attempting to bind to all available addresses" );

	if( m_Socket->Listen( m_GHost->m_BindAddress, m_HostPort ) )
		CONSOLE_Print( "[GAME: " + m_GameName + "] listening on port " + UTIL_ToString( m_HostPort ) );
	else
	{
		CONSOLE_Print( "[GAME: " + m_GameName + "] error listening on port " + UTIL_ToString( m_HostPort ) );
		m_Exiting = true;
	}
}

CBaseGame :: ~CBaseGame( )
{
	// save replay
	// todotodo: put this in a thread

	if( m_Replay && ( m_GameLoading || m_GameLoaded ) )
	{
		time_t Now = time( NULL );
		char Time[17];
		memset( Time, 0, sizeof( char ) * 17 );
		strftime( Time, sizeof( char ) * 17, "%Y-%m-%d %H-%M", localtime( &Now ) );
		string MinString = UTIL_ToString( ( m_GameTicks / 1000 ) / 60 );
		string SecString = UTIL_ToString( ( m_GameTicks / 1000 ) % 60 );

		if( MinString.size( ) == 1 )
			MinString.insert( 0, "0" );

		if( SecString.size( ) == 1 )
			SecString.insert( 0, "0" );

		m_Replay->BuildReplay( m_GameName, m_StatString, m_GHost->m_ReplayWar3Version, m_GHost->m_ReplayBuildNumber );
		if (m_GHost->m_GameIDReplays)
		{
			// By uakf.b
			if(m_DatabaseID == 0)
				m_Replay->Save( m_GHost->m_TFT, m_GHost->m_ReplayPath + UTIL_FileSafeName( "GHost++ " + string( Time ) + " " + m_GameName + " (" + MinString + "m" + SecString + "s).w3g" ) );
			else
				m_Replay->Save( m_GHost->m_TFT, m_GHost->m_ReplayPath + UTIL_FileSafeName( "GHost++ " + UTIL_ToString( m_DatabaseID ) + ".w3g" ) );
		}
		else
		{
			m_Replay->Save( m_GHost->m_TFT, m_GHost->m_ReplayPath + UTIL_FileSafeName( "GHost++ " + string( Time ) + " " + m_GameName + " (" + MinString + "m" + SecString + "s).w3g" ) );
		}
	}

	delete m_Socket;
	delete m_Protocol;
	delete m_Map;
	delete m_Replay;

        for( vector<CPotentialPlayer *> :: iterator i = m_Potentials.begin( ); i != m_Potentials.end( ); ++i )
		delete *i;

        for( vector<CGamePlayer *> :: iterator i = m_Players.begin( ); i != m_Players.end( ); ++i )
		delete *i;

        for( vector<CCallableScoreCheck *> :: iterator i = m_ScoreChecks.begin( ); i != m_ScoreChecks.end( ); ++i )
		m_GHost->m_Callables.push_back( *i );

        for( vector<PairedWPCheck> :: iterator i = m_PairedWPChecks.begin( ); i != m_PairedWPChecks.end( ); ++i )
                m_GHost->m_Callables.push_back( i->second );

        for( vector<PairedBanCheck2> :: iterator i = m_PairedBanCheck2s.begin( ); i != m_PairedBanCheck2s.end( ); ++i )
                m_GHost->m_Callables.push_back( i->second );

        for( vector<Pairedpenp> :: iterator i = m_Pairedpenps.begin( ); i != m_Pairedpenps.end( ); ++i )
                m_GHost->m_Callables.push_back( i->second );

        for( vector<PairedBanAdd> :: iterator i = m_PairedBanAdds.begin( ); i != m_PairedBanAdds.end( ); ++i )
                m_GHost->m_Callables.push_back( i->second );

        for( vector<PairedFromCheck> :: iterator i = m_PairedFromChecks.begin( ); i != m_PairedFromChecks.end( ); ++i )
                m_GHost->m_Callables.push_back( i->second );

	for( vector<PairedLogUpdate> :: iterator i = m_PairedLogUpdates.begin( ); i != m_PairedLogUpdates.end( ); ++i )
                m_GHost->m_Callables.push_back( i->second );

	while( !m_Actions.empty( ) )
	{
		delete m_Actions.front( );
		m_Actions.pop( );
	}
}

uint32_t CBaseGame :: GetNextTimedActionTicks( )
{
	// return the number of ticks (ms) until the next "timed action", which for our purposes is the next game update
	// the main GHost++ loop will make sure the next loop update happens at or before this value
	// note: there's no reason this function couldn't take into account the game's other timers too but they're far less critical
	// warning: this function must take into account when actions are not being sent (e.g. during loading or lagging)

	if( !m_GameLoaded || m_Lagging )
		return 50;

	uint32_t TicksSinceLastUpdate = GetTicks( ) - m_LastActionSentTicks;

	if( TicksSinceLastUpdate > m_Latency - m_LastActionLateBy )
		return 0;
	else
		return m_Latency - m_LastActionLateBy - TicksSinceLastUpdate;
}

string CBaseGame :: GetMapName( )
{
	return m_Map->GetMapPath();
}

uint32_t CBaseGame :: GetSlotsOccupied( )
{
	uint32_t NumSlotsOccupied = 0;

        for( vector<CGameSlot> :: iterator i = m_Slots.begin( ); i != m_Slots.end( ); ++i )
	{
		if( (*i).GetSlotStatus( ) == SLOTSTATUS_OCCUPIED )
                        ++NumSlotsOccupied;
	}

	return NumSlotsOccupied;
}

uint32_t CBaseGame :: GetSlotsAllocated( )
{
	uint32_t SlotsOccupied = GetSlotsOccupied( );
	uint32_t NumPlayers = m_Players.size( );

	if( NumPlayers > SlotsOccupied )
		return NumPlayers;
	else
		return SlotsOccupied;
}

uint32_t CBaseGame :: GetSlotsOpen( )
{
	uint32_t NumSlotsOpen = 0;

        for( vector<CGameSlot> :: iterator i = m_Slots.begin( ); i != m_Slots.end( ); ++i )
	{
		if( (*i).GetSlotStatus( ) == SLOTSTATUS_OPEN )
                        ++NumSlotsOpen;
	}

	return NumSlotsOpen;
}

uint32_t CBaseGame :: GetNumPlayers( )
{
	uint32_t NumPlayers = GetNumHumanPlayers( );

	if( m_FakePlayerPID != 255 )
                ++NumPlayers;

	return NumPlayers;
}

uint32_t CBaseGame :: GetNumHumanPlayers( )
{
	uint32_t NumHumanPlayers = 0;

        for( vector<CGamePlayer *> :: iterator i = m_Players.begin( ); i != m_Players.end( ); ++i )
	{
		if( !(*i)->GetLeftMessageSent( ) )
                        ++NumHumanPlayers;
	}

	return NumHumanPlayers;
}

string CBaseGame :: GetDescription( )
{
	string Description = m_GameName + " : " + m_OwnerName + " : " + UTIL_ToString( GetNumHumanPlayers( ) ) + "/" + UTIL_ToString( m_GameLoading || m_GameLoaded ? m_StartPlayers : m_Slots.size( ) );

	if( m_GameLoading || m_GameLoaded )
		Description += " : " + UTIL_ToString( ( m_GameTicks / 1000 ) / 60 ) + "m";
	else
		Description += " : " + UTIL_ToString( ( GetTime( ) - m_CreationTime ) / 60 ) + "m";

	return Description;
}

void CBaseGame :: SetAnnounce( uint32_t interval, string message )
{
	m_AnnounceInterval = interval;
	m_AnnounceMessage = message;
	m_LastAnnounceTime = GetTime( );
}

unsigned int CBaseGame :: SetFD( void *fd, void *send_fd, int *nfds )
{
	unsigned int NumFDs = 0;

	if( m_Socket )
	{
		m_Socket->SetFD( (fd_set *)fd, (fd_set *)send_fd, nfds );
                ++NumFDs;
	}

        for( vector<CPotentialPlayer *> :: iterator i = m_Potentials.begin( ); i != m_Potentials.end( ); ++i )
	{
		if( (*i)->GetSocket( ) )
		{
			(*i)->GetSocket( )->SetFD( (fd_set *)fd, (fd_set *)send_fd, nfds );
                        ++NumFDs;
		}
	}

        for( vector<CGamePlayer *> :: iterator i = m_Players.begin( ); i != m_Players.end( ); ++i )
	{
		if( (*i)->GetSocket( ) )
		{
			(*i)->GetSocket( )->SetFD( (fd_set *)fd, (fd_set *)send_fd, nfds );
                        ++NumFDs;
		}
	}

	return NumFDs;
}

bool CBaseGame :: Update( void *fd, void *send_fd )
{
	// update callables

	for( vector<CCallableScoreCheck *> :: iterator i = m_ScoreChecks.begin( ); i != m_ScoreChecks.end( ); )
	{
		if( (*i)->GetReady( ) )
		{
			double Score = (*i)->GetResult( );

                        for( vector<CPotentialPlayer *> :: iterator j = m_Potentials.begin( ); j != m_Potentials.end( ); ++j )
			{
				if( (*j)->GetJoinPlayer( ) && (*j)->GetJoinPlayer( )->GetName( ) == (*i)->GetName( ) )
					EventPlayerJoinedWithScore( *j, (*j)->GetJoinPlayer( ), Score );
			}

			m_GHost->m_DB->RecoverCallable( *i );
			delete *i;
			i = m_ScoreChecks.erase( i );
		}
		else
                        ++i;
	}

        for( vector<PairedWPCheck> :: iterator i = m_PairedWPChecks.begin( ); i != m_PairedWPChecks.end( ); )
        {
                if( i->second->GetReady( ) )
                {
                        CDBStatsPlayerSummary *StatsPlayerSummary = i->second->GetResult( );
                        if( StatsPlayerSummary )
                        {
				CGamePlayer *Player = GetPlayerFromName( i->first, true );
	                        if( Player )
				{
	                                uint32_t wins = StatsPlayerSummary->GetWins( );
					uint32_t losses = StatsPlayerSummary->GetLosses( );
					if( wins >= 1 )
					{
						float playerwinperc = ( ( 100*wins ) / ( wins + losses ) );
						Player->SetWinPerc( playerwinperc );
						Player->SetGames( StatsPlayerSummary->GetGames( ) );
					}
					Player->SetLeavePerc( StatsPlayerSummary->GetLeavePerc( ) );
					Player->SetForcedGproxy( StatsPlayerSummary->GetForcedGproxy( ) );
				}
              		}

                        m_GHost->m_DB->RecoverCallable( i->second );
                        delete i->second;
                        i = m_PairedWPChecks.erase( i );
                }
                else
                        ++i;
        }

        for( vector<PairedBanCheck2> :: iterator i = m_PairedBanCheck2s.begin( ); i != m_PairedBanCheck2s.end( ); )
        {
                if( i->second->GetReady( ) )
                {
                        string Result = i->second->GetResult( );
                        if( UTIL_ToUInt32( Result ) >= 5 && i->second->GetType() == "joincheck" && m_GHost->m_CheckIPRange )
				SendAllChat( "Potential banavoider joined: " + i->second->GetUser() + " has " + Result + " bans on the IPRange" );

                        m_GHost->m_DB->RecoverCallable( i->second );
                        delete i->second;
                        i = m_PairedBanCheck2s.erase( i );
                }
                else
                        ++i;
        }

        for( vector<Pairedpenp> :: iterator i = m_Pairedpenps.begin( ); i != m_Pairedpenps.end( ); )
        {
                if( i->second->GetReady( ) )
                {
                        uint32_t Result = i->second->GetResult( );
                        if( i->second->GetType( ) == "check" )
                        {
                                if( Result != 0 )
                                        SendAllChat( "User [" + i->second->GetName() + "] has [" + UTIL_ToString( Result ) + "] penality point(s)." );
                                else
                                        SendAllChat( "User [" + i->second->GetName() + "] has no penality points." );
                        }
                        if( i->second->GetType( ) == "checkall" )
                        {
                                if( Result != 0 )
                                        SendAllChat( "User [" + i->second->GetName() + "] has [" + UTIL_ToString( Result ) + "] penality point(s)." );
                                else
                                        SendAllChat( "User [" + i->second->GetName() + "] has no penality points." );
                        }
                        else if( i->second->GetType( ) == "add" )
                        {
                                if( Result == 1 )
                                        SendAllChat( "Added [" + UTIL_ToString( i->second->GetAmount( ) ) + "] penality point(s) for [" + i->second->GetName() + "] by [" + i->second->GetAdmin() + "]" );
				else if(  Result == 2 )
					SendAllChat( "User [" + i->second->GetName() + "] got banned by reaching to many penality points" );
                                else
                                        CONSOLE_Print( "Couldn't add a penality point" );
                        }
                        else
                                CONSOLE_Print( "Error. Something gone wrong" );

                        m_GHost->m_DB->RecoverCallable( i->second );
                        delete i->second;
                        i = m_Pairedpenps.erase( i );
                }
                else
                        ++i;
        }

        for( vector<PairedFromCheck> :: iterator i = m_PairedFromChecks.begin( ); i != m_PairedFromChecks.end( ); )
        {
                if( i->second->GetReady( ) )
                {
			string Result = i->second->GetResult( );
                        CGamePlayer *Player = GetPlayerFromName( i->first, true );
                        if( Player )
                        {
				stringstream SS;
				string CountryLetter;
				string Country;
				SS << Result;
				SS >> CountryLetter;
				SS >> Country;
				Player->SetCLetter( CountryLetter );
				Player->SetCountry( Country );
			}
                        m_GHost->m_DB->RecoverCallable( i->second );
                        delete i->second;
                        i = m_PairedFromChecks.erase( i );
                }
                else
                        ++i;
        }

        for( vector<PairedBanAdd> :: iterator i = m_PairedBanAdds.begin( ); i != m_PairedBanAdds.end( ); )
        {
              if( i->second->GetReady( ) )
                {
			string Result = i->second->GetResult( );

			if( Result == "alreadypermbanned" )
				SendAllChat( "Error banning user. The User ["+i->second->GetUser( )+"] is already permanent banned" );
			else if( Result == "alreadybannedwithhigheramount" )
				SendAllChat( "Error banning user. The User ["+i->second->GetUser( )+"] is already banned for a longer amount." );
			else if( !Result.empty() && Result != "failed" )
			{
                                for( vector<CBNET *> :: iterator j = m_GHost->m_BNETs.begin( ); j != m_GHost->m_BNETs.end( ); ++j )
       	                        {
               	                        if( (*j)->GetServer( ) == i->second->GetServer( ) )
                       	                        (*j)->AddBan( i->second->GetUser( ), i->second->GetIP( ), i->second->GetGameName( ), i->second->GetAdmin( ), i->second->GetReason( ) );
                               	}
                                SendAllChat( Result );
			}
			else
				SendAllChat( "Something gone wrong, report this please to Grief-Code." );

                        m_GHost->m_DB->RecoverCallable( i->second );
                        delete i->second;
                        i = m_PairedBanAdds.erase( i );
                }
                else
                        ++i;
        }

        for( vector<PairedLogUpdate> :: iterator i = m_PairedLogUpdates.begin( ); i != m_PairedLogUpdates.end( ); )
        {
                if( i->second->GetReady( ) )
                {
                        m_GHost->m_DB->RecoverCallable( i->second );
                        delete i->second;
                        i = m_PairedLogUpdates.erase( i );
                }
                else
                        ++i;
        }

	// update players

	for( vector<CGamePlayer *> :: iterator i = m_Players.begin( ); i != m_Players.end( ); )
	{
		if( (*i)->Update( fd ) )
		{
			EventPlayerDeleted( *i );
			delete *i;
			i = m_Players.erase( i );
		}
		else
                        ++i;
	}

	for( vector<CPotentialPlayer *> :: iterator i = m_Potentials.begin( ); i != m_Potentials.end( ); )
	{
		if( (*i)->Update( fd ) )
		{
			// flush the socket (e.g. in case a rejection message is queued)

			if( (*i)->GetSocket( ) )
				(*i)->GetSocket( )->DoSend( (fd_set *)send_fd );

			delete *i;
			i = m_Potentials.erase( i );
		}
		else
                        ++i;
	}

	// create the virtual host player

	if( !m_GameLoading && !m_GameLoaded && GetSlotsAllocated() < m_Slots.size( ) )
		CreateVirtualHost( );

	// unlock the game

	if( m_Locked && !GetPlayerFromName( m_OwnerName, false ) )
	{
		SendAllChat( m_GHost->m_Language->GameUnlocked( ) );
		m_Locked = false;
	}

	// ping every 5 seconds
	// changed this to ping during game loading as well to hopefully fix some problems with people disconnecting during loading
	// changed this to ping during the game as well

	if( GetTime( ) - m_LastPingTime >= 5 )
	{
		// note: we must send pings to players who are downloading the map because Warcraft III disconnects from the lobby if it doesn't receive a ping every ~90 seconds
		// so if the player takes longer than 90 seconds to download the map they would be disconnected unless we keep sending pings
		// todotodo: ignore pings received from players who have recently finished downloading the map

		SendAll( m_Protocol->SEND_W3GS_PING_FROM_HOST( ) );

		// we also broadcast the game to the local network every 5 seconds so we hijack this timer for our nefarious purposes
		// however we only want to broadcast if the countdown hasn't started
		// see the !sendlan code later in this file for some more information about how this works
		// todotodo: should we send a game cancel message somewhere? we'll need to implement a host counter for it to work

		if( !m_CountDownStarted )
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

			uint32_t slotstotal = m_Slots.size( );
			uint32_t slotsopen = GetSlotsOpen();
			if (slotsopen<2) slotsopen = 2;
			if(slotstotal > 12) slotstotal = 12;

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
				m_GHost->m_UDPSocket->Broadcast( 6112, m_Protocol->SEND_W3GS_GAMEINFO( m_GHost->m_TFT, m_GHost->m_LANWar3Version, UTIL_CreateByteArray( MapGameType, false ), m_Map->GetMapGameFlags( ), MapWidth, MapHeight, m_GameName, "Varlock", GetTime( ) - m_CreationTime, "Save\\Multiplayer\\" + m_SaveGame->GetFileNameNoPath( ), m_SaveGame->GetMagicNumber( ), slotstotal, slotsopen, m_HostPort, FixedHostCounter, m_EntryKey ) );
			}
			else
			{
				// note: the PrivateGame flag is not set when broadcasting to LAN (as you might expect)
				// note: we do not use m_Map->GetMapGameType because none of the filters are set when broadcasting to LAN (also as you might expect)

				uint32_t MapGameType = MAPGAMETYPE_UNKNOWN0;
				m_GHost->m_UDPSocket->Broadcast( 6112, m_Protocol->SEND_W3GS_GAMEINFO( m_GHost->m_TFT, m_GHost->m_LANWar3Version, UTIL_CreateByteArray( MapGameType, false ), m_Map->GetMapGameFlags( ), m_Map->GetMapWidth( ), m_Map->GetMapHeight( ), m_GameName, "Varlock", GetTime( ) - m_CreationTime, m_Map->GetMapPath( ), m_Map->GetMapCRC( ), slotstotal, slotsopen, m_HostPort, FixedHostCounter, m_EntryKey ) );

			}
		}

		m_LastPingTime = GetTime( );
	}

	// auto rehost if there was a refresh error in autohosted games

	if( m_RefreshError && !m_CountDownStarted && m_GameState == GAME_PUBLIC && !m_GHost->m_AutoHostGameName.empty( ) && m_GHost->m_AutoHostMaximumGames != 0 && m_GHost->m_AutoHostAutoStartPlayers != 0 && m_AutoStartPlayers != 0 )
	{
		// there's a slim chance that this isn't actually an autohosted game since there is no explicit autohost flag
		// however, if autohosting is enabled and this game is public and this game is set to autostart, it's probably autohosted
		// so rehost it using the current autohost game name

		string GameName = m_GHost->m_AutoHostGameName + " #" + UTIL_ToString( m_GHost->m_HostCounter );
		CONSOLE_Print( "[GAME: " + m_GameName + "] automatically trying to rehost as public game [" + GameName + "] due to refresh failure" );
		m_LastGameName = m_GameName;
		m_GameName = GameName;
		m_HostCounter = m_GHost->m_HostCounter++;
		m_GHost->SaveHostCounter();
		m_HostCounter = m_GHost->m_HostCounter;
		m_RefreshError = false;

                for( vector<CBNET *> :: iterator i = m_GHost->m_BNETs.begin( ); i != m_GHost->m_BNETs.end( ); ++i )
		{
			(*i)->QueueGameUncreate( );
			(*i)->QueueEnterChat( );

			// the game creation message will be sent on the next refresh
		}

		m_CreationTime = GetTime( );
		m_LastRefreshTime = GetTime( );
	}

	// refresh every 3 seconds

	if( !m_RefreshError && !m_CountDownStarted && m_GameState == GAME_PUBLIC && GetSlotsOpen( ) > 0 && GetTime( ) - m_LastRefreshTime >= 3 )
	{
		// send a game refresh packet to each battle.net connection

		bool Refreshed = false;

                for( vector<CBNET *> :: iterator i = m_GHost->m_BNETs.begin( ); i != m_GHost->m_BNETs.end( ); ++i )
		{
			// don't queue a game refresh message if the queue contains more than 1 packet because they're very low priority

			if( (*i)->GetOutPacketsQueued( ) <= 1 )
			{
				(*i)->QueueGameRefresh( m_GameState, m_GameName, string( ), m_Map, m_SaveGame, 0, m_HostCounter );
				Refreshed = true;
			}
		}

		// only print the "game refreshed" message if we actually refreshed on at least one battle.net server

		if( m_RefreshMessages && Refreshed )
			SendAllChat( m_GHost->m_Language->GameRefreshed( ) );

		m_LastRefreshTime = GetTime( );
	}

	// send more map data

	if( !m_GameLoading && !m_GameLoaded && GetTicks( ) - m_LastDownloadCounterResetTicks >= 1000 )
	{
		// hackhack: another timer hijack is in progress here
		// since the download counter is reset once per second it's a great place to update the slot info if necessary

		if( m_SlotInfoChanged )
			SendAllSlotInfo( );

		m_DownloadCounter = 0;
		m_LastDownloadCounterResetTicks = GetTicks( );
	}

	if( !m_GameLoading && !m_GameLoaded && GetTicks( ) - m_LastDownloadTicks >= 100 )
	{
		uint32_t Downloaders = 0;

                for( vector<CGamePlayer *> :: iterator i = m_Players.begin( ); i != m_Players.end( ); ++i )
		{
			if( (*i)->GetDownloadStarted( ) && !(*i)->GetDownloadFinished( ) )
			{
				++Downloaders;

				if( m_GHost->m_MaxDownloaders > 0 && Downloaders > m_GHost->m_MaxDownloaders )
					break;

				// send up to 100 pieces of the map at once so that the download goes faster
				// if we wait for each MAPPART packet to be acknowledged by the client it'll take a long time to download
				// this is because we would have to wait the round trip time (the ping time) between sending every 1442 bytes of map data
				// doing it this way allows us to send at least 140 KB in each round trip interval which is much more reasonable
				// the theoretical throughput is [140 KB * 1000 / ping] in KB/sec so someone with 100 ping (round trip ping, not LC ping) could download at 1400 KB/sec
				// note: this creates a queue of map data which clogs up the connection when the client is on a slower connection (e.g. dialup)
				// in this case any changes to the lobby are delayed by the amount of time it takes to send the queued data (i.e. 140 KB, which could be 30 seconds or more)
				// for example, players joining and leaving, slot changes, chat messages would all appear to happen much later for the low bandwidth player
				// note: the throughput is also limited by the number of times this code is executed each second
				// e.g. if we send the maximum amount (140 KB) 10 times per second the theoretical throughput is 1400 KB/sec
				// therefore the maximum throughput is 1400 KB/sec regardless of ping and this value slowly diminishes as the player's ping increases
				// in addition to this, the throughput is limited by the configuration value bot_maxdownloadspeed
				// in summary: the actual throughput is MIN( 140 * 1000 / ping, 1400, bot_maxdownloadspeed ) in KB/sec assuming only one player is downloading the map

				uint32_t MapSize = UTIL_ByteArrayToUInt32( m_Map->GetMapSize( ), false );

				while( (*i)->GetLastMapPartSent( ) < (*i)->GetLastMapPartAcked( ) + 1442 * 100 && (*i)->GetLastMapPartSent( ) < MapSize )
				{
					if( (*i)->GetLastMapPartSent( ) == 0 )
					{
						// overwrite the "started download ticks" since this is the first time we've sent any map data to the player
						// prior to this we've only determined if the player needs to download the map but it's possible we could have delayed sending any data due to download limits

						(*i)->SetStartedDownloadingTicks( GetTicks( ) );
					}

					// limit the download speed if we're sending too much data
					// the download counter is the # of map bytes downloaded in the last second (it's reset once per second)

					if( m_GHost->m_MaxDownloadSpeed > 0 && m_DownloadCounter > m_GHost->m_MaxDownloadSpeed * 1024 )
						break;

					Send( *i, m_Protocol->SEND_W3GS_MAPPART( GetHostPID( ), (*i)->GetPID( ), (*i)->GetLastMapPartSent( ), m_Map->GetMapData( ) ) );
					(*i)->SetLastMapPartSent( (*i)->GetLastMapPartSent( ) + 1442 );
					m_DownloadCounter += 1442;
				}
			}
		}

		m_LastDownloadTicks = GetTicks( );
	}

	// kick AFK players

	if( m_GameLoaded && ( GetTicks( ) - m_LastProcessedTicks > 1000 ) ) {
    		m_LastProcessedTicks = GetTicks( );

	 	uint32_t TimeNow = GetTime( );
		uint32_t TimeLimit = 300;

		for( vector<CGamePlayer *> :: iterator i = m_Players.begin( ); i != m_Players.end( ); i++ )
    		{
			uint32_t TimeActive = (*i)->GetTimeActive();
                        if( TimeActive > 0 && ( (TimeNow - TimeActive ) > ( TimeLimit - 120 ) ) && m_Slots[GetSIDFromPID( (*i)->GetPID( ) )].GetTeam() != 12 )
			{
				if( (*i)->GetAFKMarked( ) )
				{
                                	SendAllChat( "[ANTI-AFK] Kicking player ["+(*i)->GetName()+"] for being afk twice for 3 minutes." );
                                	(*i)->SetTimeActive( GetTime( ) );
                        	        (*i)->SetDeleteMe( true );
                	                (*i)->SetLeftReason( "was kicked by anti-afk" );
        	                        (*i)->SetLeftCode( PLAYERLEAVE_LOST );
	                                break;
				}
				else
				{
					SendAllChat( "[ANTI-AFK] Player ["+(*i)->GetName()+"] has been marked as AFK. Autokick will occur in 2 minutes." );
					(*i)->SetTimeActive( GetTime( ) );
					(*i)->SetAFKMarked( true );
				}
			}

      			if( TimeActive > 0 && ( (TimeNow - TimeActive ) > TimeLimit ) && m_Slots[GetSIDFromPID( (*i)->GetPID( ) )].GetTeam() != 12 )
      			{
        			SendAllChat( "[ANTI-AFK] Kicking player ["+(*i)->GetName()+"] for being afk more than 5 minutes." );
        			(*i)->SetTimeActive( GetTime( ) );
       				(*i)->SetDeleteMe( true );
       				(*i)->SetLeftReason( "was kicked by anti-afk" );
       				(*i)->SetLeftCode( PLAYERLEAVE_LOST );
        			break;
      			}
    		}
  	}

	// send gameloaded info
	if( !m_SendGameLoaded && m_GameLoaded && GetTime() - m_GameLoadedTime >= 300 )
	{
        	ifstream in;
	        in.open( m_GHost->m_GameLoadedFile.c_str( ) );
        	if( !in.fail( ) )
	        {
                	// don't print more than 8 lines
        	        uint32_t Count = 0;
	                string Line;
                	while( !in.eof( ) && Count < 8 )
        	        {
	                        getline( in, Line );
                	        if( Line.empty( ) )
        	                {
	                                if( !in.eof( ) )
                                	        SendAllChat( " " );
                        	}
                	        else
        	                        SendAllChat( Line );
	                        ++Count;
	                }
        	        in.close( );
	        }
		m_SendGameLoaded = true;
	}

	// set announce
	if( m_GameLoaded && GetTime() - m_LastInGameAnnounce >= 420 )
	{
		AnnounceEvent( (rand()%(m_GHost->m_AnnounceLines-1))+1 );
		m_LastInGameAnnounce = GetTime();
	}

	// announce every m_AnnounceInterval seconds

	if( !m_AnnounceMessage.empty( ) && !m_CountDownStarted && GetTime( ) - m_LastAnnounceTime >= m_AnnounceInterval )
	{
		SendAllChat( m_AnnounceMessage );
		m_LastAnnounceTime = GetTime( );
	}

	// kick players who don't spoof check within 60 seconds when spoof checks are required and the game is autohosted

	if( !m_CountDownStarted && m_GHost->m_RequireSpoofChecks && m_GameState == GAME_PUBLIC && !m_GHost->m_AutoHostGameName.empty( ) && m_GHost->m_AutoHostMaximumGames != 0 && m_GHost->m_AutoHostAutoStartPlayers != 0 && m_AutoStartPlayers != 0 )
	{
                for( vector<CGamePlayer *> :: iterator i = m_Players.begin( ); i != m_Players.end( ); ++i )
		{
			if( !(*i)->GetSpoofed( ) && GetTime( ) - (*i)->GetJoinTime( ) >= 60 )
			{
				(*i)->SetDeleteMe( true );
				(*i)->SetLeftReason( m_GHost->m_Language->WasKickedForNotSpoofChecking( ) );
				(*i)->SetLeftCode( PLAYERLEAVE_LOBBY );
				OpenSlot( GetSIDFromPID( (*i)->GetPID( ) ), false );
			}
		}
	}

	// warn all players who joined with to less games

	// kick players within 15 seconds who dont have enough games
        if( !m_CountDownStarted && ( m_GameType == 4 || ( m_GHost->m_HighGame && m_GameType == 3 ) ) && !m_GameLoading && !m_GameLoaded )
        {
                for( vector<CGamePlayer *> :: iterator i = m_Players.begin( ); i != m_Players.end( ); ++i )
                {
                        uint32_t Level = 0;
                        for( vector<CBNET *> :: iterator k = m_GHost->m_BNETs.begin( ); k != m_GHost->m_BNETs.end( ); ++k )
                        {
                                if( (*k)->GetServer( ) == (*i)->GetSpoofedRealm( ) )
                                {
                                        Level = (*k)->IsLevel( (*i)->GetName( ) );
                                        break;
                                }
                        }

                        if( GetTime( ) - (*i)->GetJoinTime( ) >= 5 && Level == 0 && GetTime( ) - (*i)->GetAnnounceTime( ) >= 1 )
			{
				if( m_GameType == 3 )
                                {
                                        if( (*i)->GetGames( ) < m_GHost->m_MinLimit  && !IsReserved( (*i)->GetName() ) )
					{
                                      		SendChat( (*i)->GetPID( ), "[INFO] You have to less games, you require at least 50 Games. You will be kicked in ["+UTIL_ToString( 10-( GetTime()-(*i)->GetJoinTime( ) ) )+"] seconds." );
						(*i)->SetAnnounceTime( );
					}
				}
			}

                        if( GetTime( ) - (*i)->GetJoinTime( ) >= 15 && Level == 0 )
                        {
				if( m_GameType == 4 )
				{
					if( m_GHost->m_RegVIPGames && !(*i)->GetRegistered( ) && !IsReserved( (*i)->GetName() ) )
					{
                                                string name=(*i)->GetName();
                                                string realm=(*i)->GetJoinedRealm( );
						m_Denied.push_back( (*i)->GetName( ) + " " + (*i)->GetExternalIPString( ) + " " + UTIL_ToString( GetTime( ) ) );
	        	                        (*i)->SetDeleteMe( true );
        	        	                (*i)->SetLeftReason( "got kicked for not being registered." );
                	        	        (*i)->SetLeftCode( PLAYERLEAVE_LOBBY );
                        	        	OpenSlot( GetSIDFromPID( (*i)->GetPID( ) ), false );
					}
					else if( (*i)->GetGames( ) < m_GHost->m_MinVIPGames  && !IsReserved( (*i)->GetName() ) )
                	                {
                                                string name=(*i)->GetName();
                                                string realm=(*i)->GetJoinedRealm( );
						m_Denied.push_back( (*i)->GetName( ) + " " + (*i)->GetExternalIPString( ) + " " + UTIL_ToString( GetTime( ) ) );
                        	                (*i)->SetDeleteMe( true );
                                	        (*i)->SetLeftReason( "got kicked because he does not have enough games." );
                                        	(*i)->SetLeftCode( PLAYERLEAVE_LOBBY );
        	                                OpenSlot( GetSIDFromPID( (*i)->GetPID( ) ), false );
	                                }
				}
				else if( m_GameType == 3 )
				{
                                        if( (*i)->GetGames( ) < m_GHost->m_MinLimit  && !IsReserved( (*i)->GetName() ) )
                                        {
						string name=(*i)->GetName();
						string realm=(*i)->GetJoinedRealm( );
						m_Denied.push_back( (*i)->GetName( ) + " " + (*i)->GetExternalIPString( ) + " " + UTIL_ToString( GetTime( ) ) );
                                                (*i)->SetDeleteMe( true );
                                                (*i)->SetLeftReason( "got kicked because he does not have enough games." );
                                                (*i)->SetLeftCode( PLAYERLEAVE_LOBBY );
                                                OpenSlot( GetSIDFromPID( (*i)->GetPID( ) ), false );
                                        }
				}
                        }
                }
        }

	// kick players who didn't type the password within 20 seconds
        if( !m_CountDownStarted && !m_GameLoading && !m_GameLoaded )
	{
                for( vector<CGamePlayer *> :: iterator i = m_Players.begin( ); i != m_Players.end( ); ++i )
                {
		        if( (*i)->GetPasswordProt( ) && GetTime( ) - (*i)->GetJoinTime( ) >= 20 )
        		{
				m_Denied.push_back( (*i)->GetName( ) + " " + (*i)->GetExternalIPString( ) + " " + UTIL_ToString( GetTime( ) ) );
	        		(*i)->SetDeleteMe( true );
                		(*i)->SetLeftReason( "was kicked for non typing the password." );
                		(*i)->SetLeftCode( PLAYERLEAVE_LOBBY );
                		OpenSlot( GetSIDFromPID( (*i)->GetPID( ) ), false );
        		}
                }
        }

	// kick false countries
        //Countrycheck
        if( !m_CountDownStarted && !m_GameLoading && !m_GameLoaded )
        {
                for( vector<CGamePlayer *> :: iterator k = m_Players.begin( ); k != m_Players.end( ); ++k )
                {
			uint32_t Level = 0;
        		for( vector<CBNET *> :: iterator i = m_GHost->m_BNETs.begin( ); i != m_GHost->m_BNETs.end( ); ++i )
        		{
                		if( (*i)->GetServer( ) == (*k)->GetSpoofedRealm( ) )
        		        {
		                        Level = (*i)->IsLevel( (*k)->GetName( ) );
                		        break;
        		        }
		        }
			if( Level == 0 )
			{
		                string CC = (*k)->GetCLetter( );

		                transform( CC.begin( ), CC.end( ), CC.begin( ), (int(*)(int))toupper );
        		        bool unallowedcountry = false;
                		for( vector<string> :: iterator i = m_LimitedCountries.begin( ); i != m_LimitedCountries.end( ); )
	                	{
        	                	if( *i == CC && m_DenieCountries )
                	                	unallowedcountry = true;

		                        if( *i != CC && m_LimitCountries )
        		                        unallowedcountry = true;
					i++;
                		}

		                for( vector<string> :: iterator i = m_GHost->m_DCountries.begin( ); i != m_GHost->m_DCountries.end( ); )
        		        {
                		        if( *i == CC )
                        		        unallowedcountry = true;
					i++;
		                }

				if( m_GHost->m_DenieProxy )
				{
					if( CC == "a1" )
					{
                                        	SendAllChat( "Autokicking ["+(*k)->GetName()+"] for joining with a Proxy." );
                                	        m_Denied.push_back( (*k)->GetName( ) + " " + (*k)->GetExternalIPString( ) + " " + UTIL_ToString( GetTime( ) ) );
                        	                (*k)->SetDeleteMe( true );
                	                        (*k)->SetLeftReason( "was kicked for joining with a proxy." );
        	                                (*k)->SetLeftCode( PLAYERLEAVE_LOBBY );
	                                        OpenSlot( GetSIDFromPID( (*k)->GetPID( ) ), false );
					}
				}

        		        if( unallowedcountry )
                		{
                        		SendAllChat( "Autokicking User ["+(*k)->GetName()+"] for joining with a denied country: " + CC );
                                	m_Denied.push_back( (*k)->GetName( ) + " " + (*k)->GetExternalIPString( ) + " " + UTIL_ToString( GetTime( ) ) );
	                                (*k)->SetDeleteMe( true );
        	                        (*k)->SetLeftReason( "was kicked for having a denied country." );
                	                (*k)->SetLeftCode( PLAYERLEAVE_LOBBY );
                        	        OpenSlot( GetSIDFromPID( (*k)->GetPID( ) ), false );
				}
			}
                }
        }

	// try to auto start every 10 seconds

	if( !m_CountDownStarted && m_AutoStartPlayers != 0 && GetTime( ) - m_LastAutoStartTime >= 15 )
	{
		StartCountDownAuto( m_GHost->m_RequireSpoofChecks );
		m_LastAutoStartTime = GetTime( );
	}

	// countdown every 500 ms

	if( m_CountDownStarted && GetTicks( ) - m_LastCountDownTicks >= 500 && !m_GameLoaded )
	{
		if( m_CountDownCounter > 0 )
		{
			// we use a countdown counter rather than a "finish countdown time" here because it might alternately round up or down the count
			// this sometimes resulted in a countdown of e.g. "6 5 3 2 1" during my testing which looks pretty dumb
			// doing it this way ensures it's always "5 4 3 2 1" but each interval might not be *exactly* the same length

			SendAllChat( UTIL_ToString( m_CountDownCounter ) + "..." );
			--m_CountDownCounter;
		}
		else if( !m_GameLoading && !m_GameLoaded )
			EventGameStarted( );

		m_LastCountDownTicks = GetTicks( );
	}

	// check if the lobby is "abandoned" and needs to be closed since it will never start

	if( !m_GameLoading && !m_GameLoaded && m_AutoStartPlayers == 0 && m_GHost->m_LobbyTimeLimit > 0 )
	{
		// check if there's a player with reserved status in the game

                for( vector<CGamePlayer *> :: iterator i = m_Players.begin( ); i != m_Players.end( ); ++i )
		{
			if( (*i)->GetReserved( ) )
				m_LastReservedSeen = GetTime( );
		}

		// check if we've hit the time limit

		if( GetTime( ) - m_LastReservedSeen >= m_GHost->m_LobbyTimeLimit * 60 )
		{
			CONSOLE_Print( "[GAME: " + m_GameName + "] is over (lobby time limit hit)" );
			return true;
		}
	}

	// check if the game is loaded

	if( m_GameLoading )
	{
		bool FinishedLoading = true;

                for( vector<CGamePlayer *> :: iterator i = m_Players.begin( ); i != m_Players.end( ); ++i )
		{
			FinishedLoading = (*i)->GetFinishedLoading( );

			if( !FinishedLoading )
				break;
		}

		if( FinishedLoading )
		{
			m_LastActionSentTicks = GetTicks( );
			m_GameLoading = false;
			m_GameLoaded = true;
			EventGameLoaded( );
		}
		else
		{
			// reset the "lag" screen (the load-in-game screen) every 30 seconds

			if( m_LoadInGame && GetTime( ) - m_LastLagScreenResetTime >= 30 )
			{
				bool UsingGProxy = false;

                                for( vector<CGamePlayer *> :: iterator i = m_Players.begin( ); i != m_Players.end( ); ++i )
				{
					if( (*i)->GetGProxy( ) )
						UsingGProxy = true;
				}

                                for( vector<CGamePlayer *> :: iterator i = m_Players.begin( ); i != m_Players.end( ); ++i )
				{
					if( (*i)->GetFinishedLoading( ) )
					{
						// stop the lag screen

                                                for( vector<CGamePlayer *> :: iterator j = m_Players.begin( ); j != m_Players.end( ); ++j )
						{
							if( !(*j)->GetFinishedLoading( ) )
								Send( *i, m_Protocol->SEND_W3GS_STOP_LAG( *j, true ) );
						}

						// send an empty update
						// this resets the lag screen timer but creates a rather annoying problem
						// in order to prevent a desync we must make sure every player receives the exact same "desyncable game data" (updates and player leaves) in the exact same order
						// unfortunately we cannot send updates to players who are still loading the map, so we buffer the updates to those players (see the else clause a few lines down for the code)
						// in addition to this we must ensure any player leave messages are sent in the exact same position relative to these updates so those must be buffered too

						if( UsingGProxy && !(*i)->GetGProxy( ) )
						{
							// we must send empty actions to non-GProxy++ players
							// GProxy++ will insert these itself so we don't need to send them to GProxy++ players
							// empty actions are used to extend the time a player can use when reconnecting

                                                        for( unsigned char j = 0; j < m_GProxyEmptyActions; ++j )
								Send( *i, m_Protocol->SEND_W3GS_INCOMING_ACTION( queue<CIncomingAction *>( ), 0 ) );
						}

						Send( *i, m_Protocol->SEND_W3GS_INCOMING_ACTION( queue<CIncomingAction *>( ), 0 ) );

						// start the lag screen

						Send( *i, m_Protocol->SEND_W3GS_START_LAG( m_Players, true ) );
					}
					else
					{
						// buffer the empty update since the player is still loading the map

						if( UsingGProxy && !(*i)->GetGProxy( ) )
						{
							// we must send empty actions to non-GProxy++ players
							// GProxy++ will insert these itself so we don't need to send them to GProxy++ players
							// empty actions are used to extend the time a player can use when reconnecting

                                                        for( unsigned char j = 0; j < m_GProxyEmptyActions; ++j )
								(*i)->AddLoadInGameData( m_Protocol->SEND_W3GS_INCOMING_ACTION( queue<CIncomingAction *>( ), 0 ) );
						}

						(*i)->AddLoadInGameData( m_Protocol->SEND_W3GS_INCOMING_ACTION( queue<CIncomingAction *>( ), 0 ) );
					}
				}

				// add actions to replay

				if( m_Replay )
				{
					if( UsingGProxy )
					{
                                                for( unsigned char i = 0; i < m_GProxyEmptyActions; ++i )
							m_Replay->AddTimeSlot( 0, queue<CIncomingAction *>( ) );
					}

					m_Replay->AddTimeSlot( 0, queue<CIncomingAction *>( ) );
				}

				// Warcraft III doesn't seem to respond to empty actions

				/* if( UsingGProxy )
					m_SyncCounter += m_GProxyEmptyActions;

				m_SyncCounter++; */
				m_LastLagScreenResetTime = GetTime( );
			}
		}
	}

	// keep track of the largest sync counter (the number of keepalive packets received by each player)
	// if anyone falls behind by more than m_SyncLimit keepalives we start the lag screen

	if( m_GameLoaded )
	{
		// check if anyone has started lagging
		// we consider a player to have started lagging if they're more than m_SyncLimit keepalives behind

		if( !m_Lagging )
		{
			string LaggingString;

                        for( vector<CGamePlayer *> :: iterator i = m_Players.begin( ); i != m_Players.end( ); ++i )
			{
				if( m_SyncCounter - (*i)->GetSyncCounter( ) > m_SyncLimit )
				{
					(*i)->SetLagging( true );
					(*i)->SetStartedLaggingTicks( GetTicks( ) );
					m_Lagging = true;
					m_StartedLaggingTime = GetTime( );

					if( LaggingString.empty( ) )
						LaggingString = (*i)->GetName( );
					else
						LaggingString += ", " + (*i)->GetName( );
				}
			}

			if( m_Lagging )
			{
				// start the lag screen

				CONSOLE_Print( "[GAME: " + m_GameName + "] started lagging on [" + LaggingString + "]" );
				GAME_Print( 11, "", "", LaggingString, "", "start" );
				SendAll( m_Protocol->SEND_W3GS_START_LAG( m_Players ) );

				// reset everyone's drop vote

                                for( vector<CGamePlayer *> :: iterator i = m_Players.begin( ); i != m_Players.end( ); ++i )
					(*i)->SetDropVote( false );

				m_LastLagScreenResetTime = GetTime( );
			}
		}

		if( m_Lagging )
		{
			bool UsingGProxy = false;

                        for( vector<CGamePlayer *> :: iterator i = m_Players.begin( ); i != m_Players.end( ); ++i )
			{
				if( (*i)->GetGProxy( ) )
				{
					UsingGProxy = true;
					(*i)->SetSafeDrop( true );
				}
			}

			uint32_t WaitTime = 60;

			if( UsingGProxy )
				WaitTime = ( m_GProxyEmptyActions + 1 ) * 60;

			if( GetTime( ) - m_StartedLaggingTime >= WaitTime )
			{
				StopLaggers( m_GHost->m_Language->WasAutomaticallyDroppedAfterSeconds( UTIL_ToString( WaitTime ) ) );
			}
			// we cannot allow the lag screen to stay up for more than ~65 seconds because Warcraft III disconnects if it doesn't receive an action packet at least this often
			// one (easy) solution is to simply drop all the laggers if they lag for more than 60 seconds
			// another solution is to reset the lag screen the same way we reset it when using load-in-game
			// this is required in order to give GProxy++ clients more time to reconnect

			if( GetTime( ) - m_LastLagScreenResetTime >= 60 )
			{
                                for( vector<CGamePlayer *> :: iterator i = m_Players.begin( ); i != m_Players.end( ); ++i )
				{
					// stop the lag screen

                                        for( vector<CGamePlayer *> :: iterator j = m_Players.begin( ); j != m_Players.end( ); ++j )
					{
						if( (*j)->GetLagging( ) )
						{
							GAME_Print( 11, "", "", (*j)->GetName(), "", "stop" );
							Send( *i, m_Protocol->SEND_W3GS_STOP_LAG( *j ) );
						}
					}

					// send an empty update
					// this resets the lag screen timer

					if( UsingGProxy && !(*i)->GetGProxy( ) )
					{
						// we must send additional empty actions to non-GProxy++ players
						// GProxy++ will insert these itself so we don't need to send them to GProxy++ players
						// empty actions are used to extend the time a player can use when reconnecting

                                                for( unsigned char j = 0; j < m_GProxyEmptyActions; ++j )
							Send( *i, m_Protocol->SEND_W3GS_INCOMING_ACTION( queue<CIncomingAction *>( ), 0 ) );
					}

					Send( *i, m_Protocol->SEND_W3GS_INCOMING_ACTION( queue<CIncomingAction *>( ), 0 ) );

					// start the lag screen

					Send( *i, m_Protocol->SEND_W3GS_START_LAG( m_Players ) );
				}

				// add actions to replay

				if( m_Replay )
				{
					if( UsingGProxy )
					{
                                                for( unsigned char i = 0; i < m_GProxyEmptyActions; ++i )
							m_Replay->AddTimeSlot( 0, queue<CIncomingAction *>( ) );
					}

					m_Replay->AddTimeSlot( 0, queue<CIncomingAction *>( ) );
				}

				// Warcraft III doesn't seem to respond to empty actions

				/* if( UsingGProxy )
					m_SyncCounter += m_GProxyEmptyActions;

				m_SyncCounter++; */
				m_LastLagScreenResetTime = GetTime( );
			}

			// check if anyone has stopped lagging normally
			// we consider a player to have stopped lagging if they're less than half m_SyncLimit keepalives behind

                        for( vector<CGamePlayer *> :: iterator i = m_Players.begin( ); i != m_Players.end( ); ++i )
			{
				if( (*i)->GetLagging( ) && m_SyncCounter - (*i)->GetSyncCounter( ) < m_SyncLimit / 2 )
				{
					// stop the lag screen for this player

					CONSOLE_Print( "[GAME: " + m_GameName + "] stopped lagging on [" + (*i)->GetName( ) + "]" );
					SendAll( m_Protocol->SEND_W3GS_STOP_LAG( *i ) );
					(*i)->SetLagging( false );
					(*i)->SetStartedLaggingTicks( 0 );
				}
			}

			// check if everyone has stopped lagging

			bool Lagging = false;

                        for( vector<CGamePlayer *> :: iterator i = m_Players.begin( ); i != m_Players.end( ); ++i )
			{
				if( (*i)->GetLagging( ) )
					Lagging = true;
			}

			m_Lagging = Lagging;

			// reset m_LastActionSentTicks because we want the game to stop running while the lag screen is up

			m_LastActionSentTicks = GetTicks( );

			// keep track of the last lag screen time so we can avoid timing out players

			m_LastLagScreenTime = GetTime( );
		}
	}

	// send actions every m_Latency milliseconds
	// actions are at the heart of every Warcraft 3 game but luckily we don't need to know their contents to relay them
	// we queue player actions in EventPlayerAction then just resend them in batches to all players here

	if( m_GameLoaded && !m_Lagging && GetTicks( ) - m_LastActionSentTicks >= m_Latency - m_LastActionLateBy )
		SendAllActions( );

	// expire the votekick

	if( !m_KickVotePlayer.empty( ) && GetTime( ) - m_StartedKickVoteTime >= 60 )
	{
		CONSOLE_Print( "[GAME: " + m_GameName + "] votekick against player [" + m_KickVotePlayer + "] expired" );
		SendAllChat( m_GHost->m_Language->VoteKickExpired( m_KickVotePlayer ) );
		m_KickVotePlayer.clear( );
		m_StartedKickVoteTime = 0;
	}

	// start the gameover timer if there's only one player left

	if( m_Players.size( ) == 1 && m_FakePlayerPID == 255 && m_GameOverTime == 0 && ( m_GameLoading || m_GameLoaded ) )
	{
		CONSOLE_Print( "[GAME: " + m_GameName + "] gameover timer started (one player left)" );
		m_GameOverTime = GetTime( );
		m_GHost->m_Callables.push_back( m_GHost->m_DB->Threadedgs( m_ChatID, string(), 3, uint32_t() ) );
	}

	// finish the gameover timer

	if( m_GameOverTime != 0 && GetTime( ) - m_GameOverTime >= 10 )
	{
		bool AlreadyStopped = true;

                for( vector<CGamePlayer *> :: iterator i = m_Players.begin( ); i != m_Players.end( ); ++i )
		{
			if( !(*i)->GetDeleteMe( ) )
			{
				AlreadyStopped = false;
				break;
			}
		}

		if( !AlreadyStopped )
		{
			CONSOLE_Print( "[GAME: " + m_GameName + "] is over (gameover timer finished)" );
			StopPlayers( "was disconnected (gameover timer finished)" );
		}
	}

	// end the game if there aren't any players left

	if( m_Players.empty( ) && ( m_GameLoading || m_GameLoaded ) )
	{
		if( !m_Saving )
		{
			CONSOLE_Print( "[GAME: " + m_GameName + "] is over (no players left)" );
			SaveGameData( );
			m_Saving = true;
			m_GHost->m_Callables.push_back( m_GHost->m_DB->Threadedgs( m_ChatID, string(), 3, uint32_t() ) );
		}
		else if( IsGameDataSaved( ) )
			return true;
	}

	// accept new connections

	if( m_Socket )
	{
		CTCPSocket *NewSocket = m_Socket->Accept( (fd_set *)fd );

		if( NewSocket )
		{
			// check the IP blacklist

			if( m_IPBlackList.find( NewSocket->GetIPString( ) ) == m_IPBlackList.end( ) )
			{
				if( m_GHost->m_TCPNoDelay )
					NewSocket->SetNoDelay( true );

				m_Potentials.push_back( new CPotentialPlayer( m_Protocol, this, NewSocket ) );
			}
			else
			{
				CONSOLE_Print( "[GAME: " + m_GameName + "] rejected connection from [" + NewSocket->GetIPString( ) + "] due to blacklist" );
				delete NewSocket;
			}
		}

		if( m_Socket->HasError( ) )
			return true;
	}

        if( m_PauseReq && GetTime() - m_PauseIntroTime >= 5 )
        {
                if( !m_Paused && GetTicks( ) - m_LastCountDownTicks >= 1000 )
                {
                        if( m_PauseTicks > 0 )
                        {
                                SendAllChat( "Game will be paused in " + UTIL_ToString( m_PauseTicks )  + ". . ." );
                                --m_PauseTicks;
                        }
                        else if( m_PauseTicks == 0 )
                        {
                                BYTEARRAY CRC;
                                BYTEARRAY Action;
                                Action.push_back( 1 );
                                m_Actions.push( new CIncomingAction( m_FakePlayerPID, CRC, Action ) );
                                m_Paused = true;
                                m_PauseTime = GetTime();
                                m_PauseTicks = 5;
				m_LastCountDownTicks = 0;
                        }
                        m_LastCountDownTicks = GetTicks( );
                }
                if( m_Paused && GetTime() - m_PauseTime == 50 && !m_SendPauseInfo )
		{
                        SendAllChat( "Game will resume in 10 seconds" );
			m_SendPauseInfo = true;
		}
                if( m_Paused && GetTime() - m_PauseTime >= 55 && GetTicks( ) - m_LastCountDownTicks >= 1000 )
                {
                        if( m_PauseTicks > 0 )
                        {
                                SendAllChat( "Game will resume in " + UTIL_ToString( m_PauseTicks )  + ". . ." );
                                --m_PauseTicks;
                        }
                        else if( m_PauseTicks == 0 )
                        {
                                BYTEARRAY CRC;
                                BYTEARRAY Action;
                                Action.push_back( 2 );
                                m_Actions.push( new CIncomingAction( m_FakePlayerPID, CRC, Action ) );
                                m_Paused = false;
                                m_PauseTime = 0;
                                m_PauseReq = false;
                                m_PauseTicks = 5;
                        }
                        m_LastCountDownTicks = GetTicks( );
                }

        }
/*
	if( GetTime( ) - m_LastLogDataUpdate >= 10 && m_GHost->m_LiveGames )
	{

		if( m_LogData != "" )
		{
			m_LogData = m_LogData + "1" + "\t" + "pl";
			//UPDATE SLOTS
			for( unsigned char i = 0; i < m_Slots.size( ); ++i )
			{
				if( m_Slots[i].GetSlotStatus( ) == SLOTSTATUS_OCCUPIED && m_Slots[i].GetComputer( ) == 0 )
				{
					CGamePlayer *player = GetPlayerFromSID( i );
					if( player )
						m_LogData = m_LogData + "\t" + player->GetName( );
					else if( !player && m_GameLoaded )
						m_LogData = m_LogData + "\t" + "-";
				}
				else if( m_Slots[i].GetSlotStatus( ) == SLOTSTATUS_OPEN )
					m_LogData = m_LogData + "\t" + "-";
			}
			m_LogData = m_LogData + "\n";
		        m_PairedLogUpdates.push_back( PairedLogUpdate( string( ), m_GHost->m_DB->ThreadedStoreLog( m_ChatID, m_LogData,  m_AdminLog ) ) );
 			m_LogData = string();
			m_AdminLog = vector<string>();
			m_PlayerUpdate = false;
			m_LastLogDataUpdate = GetTime();
		}
	}
*/
        // ping warning
        if( GetTime() - m_LastPingWarn >= 60 && m_GameLoaded )
        {
                for( vector<CGamePlayer *> :: iterator i = m_Players.begin( ); i != m_Players.end( ); ++i )
                {
                        if( (*i)->GetPing( m_GHost->m_LCPings ) >= 750 && (*i)->GetHighPingTimes( ) <= 4 )
			{
                                SendChat( (*i)->GetPID( ), "[INFO] You have a high ping of ["+UTIL_ToString( (*i)->GetPing( m_GHost->m_LCPings ) )+"] marked now for ["+UTIL_ToString((*i)->GetHighPingTimes( ))+"] times." );
				(*i)->SetHighPingTimes( );
			}
			if( (*i)->GetPing( m_GHost->m_LCPings ) >= 750 && (*i)->GetHighPingTimes( ) == 5 )
			{
				SendChat( (*i)->GetPID( ), "[INFO] You have been marked now [5] Times for a to high ping. You may leave at any time. Autoban will not occur on you." );
				SendAllChat( "[INFO] Player ["+(*i)->GetName( )+"] has been already marked [5] times with a too high ping. He can leave at any time now." );
				(*i)->SetSafeDrop( true );
				(*i)->SetHighPingTimes( );
			}
			if( (*i)->GetPing( m_GHost->m_LCPings ) >= 750 && (*i)->GetHighPingTimes( ) > 5 )
				(*i)->SetHighPingTimes( );
                }
                m_LastPingWarn = GetTime( );
        }

	return m_Exiting;
}

void CBaseGame :: UpdatePost( void *send_fd )
{
	// we need to manually call DoSend on each player now because CGamePlayer :: Update doesn't do it
	// this is in case player 2 generates a packet for player 1 during the update but it doesn't get sent because player 1 already finished updating
	// in reality since we're queueing actions it might not make a big difference but oh well

        for( vector<CGamePlayer *> :: iterator i = m_Players.begin( ); i != m_Players.end( ); ++i )
	{
		if( (*i)->GetSocket( ) )
			(*i)->GetSocket( )->DoSend( (fd_set *)send_fd );
	}

        for( vector<CPotentialPlayer *> :: iterator i = m_Potentials.begin( ); i != m_Potentials.end( ); ++i )
	{
		if( (*i)->GetSocket( ) )
			(*i)->GetSocket( )->DoSend( (fd_set *)send_fd );
	}
}

void CBaseGame :: Send( CGamePlayer *player, BYTEARRAY data )
{
	if( player )
		player->Send( data );
}

void CBaseGame :: Send( unsigned char PID, BYTEARRAY data )
{
	Send( GetPlayerFromPID( PID ), data );
}

void CBaseGame :: Send( BYTEARRAY PIDs, BYTEARRAY data )
{
        for( unsigned int i = 0; i < PIDs.size( ); ++i )
		Send( PIDs[i], data );
}

void CBaseGame :: SendAll( BYTEARRAY data )
{
        for( vector<CGamePlayer *> :: iterator i = m_Players.begin( ); i != m_Players.end( ); ++i )
		(*i)->Send( data );
}

void CBaseGame :: SendChat( unsigned char fromPID, CGamePlayer *player, string message )
{
	// send a private message to one player - it'll be marked [Private] in Warcraft 3

	if( player )
	{
		if( !m_GameLoading && !m_GameLoaded )
		{
			if( message.size( ) > 254 )
				message = message.substr( 0, 254 );

			Send( player, m_Protocol->SEND_W3GS_CHAT_FROM_HOST( fromPID, UTIL_CreateByteArray( player->GetPID( ) ), 16, BYTEARRAY( ), message ) );
		}
		else
		{
			unsigned char ExtraFlags[] = { 3, 0, 0, 0 };

			// based on my limited testing it seems that the extra flags' first byte contains 3 plus the recipient's colour to denote a private message

			unsigned char SID = GetSIDFromPID( player->GetPID( ) );

			if( SID < m_Slots.size( ) )
				ExtraFlags[0] = 3 + m_Slots[SID].GetColour( );

			if( message.size( ) > 127 )
				message = message.substr( 0, 127 );

			Send( player, m_Protocol->SEND_W3GS_CHAT_FROM_HOST( fromPID, UTIL_CreateByteArray( player->GetPID( ) ), 32, UTIL_CreateByteArray( ExtraFlags, 4 ), message ) );
		}
	}
}

void CBaseGame :: SendChat( unsigned char fromPID, unsigned char toPID, string message )
{
	SendChat( fromPID, GetPlayerFromPID( toPID ), message );
}

void CBaseGame :: SendChat( CGamePlayer *player, string message )
{
	SendChat( GetHostPID( ), player, message );
}

void CBaseGame :: SendChat( unsigned char toPID, string message )
{
	SendChat( GetHostPID( ), toPID, message );
}

void CBaseGame :: SendAllChat( unsigned char fromPID, string message )
{
	// send a public message to all players - it'll be marked [All] in Warcraft 3

	if( GetNumHumanPlayers( ) > 0 )
	{
		CONSOLE_Print( "[GAME: " + m_GameName + "] [Local]: " + message );

                string MinString = UTIL_ToString( ( m_GameTicks / 1000 ) / 60 );
                string SecString = UTIL_ToString( ( m_GameTicks / 1000 ) % 60 );

                if( MinString.size( ) == 1 )
                        MinString.insert( 0, "0" );

                if( SecString.size( ) == 1 )
                        SecString.insert( 0, "0" );

		if( m_GameLoading || m_GameLoaded )
		{
			m_LogData = m_LogData + "3" + "\t" +  "bgm" + "\t" +  "Bot" + "\t" + "-" + "\t" + "-" + "\t" + "-" + "\t" + MinString + ":" + SecString + "\t" + message + "\n";
			GAME_Print( 1, MinString, SecString, "Bot", "", message );
		}
		else
		{
			m_LogData = m_LogData + "2" + "\t" +  "blm" + "\t" + "Bot" + "\t" + "-" + "\t" + "-" + "\t" + "-" + "\t" + "-" + "\t" + message + "\n";
                        if( message.find( "@" ) == string::npos )
                                GAME_Print( 0, "", "", "Bot", "", message );
		}
		if( !m_GameLoading && !m_GameLoaded )
		{
			if( message.size( ) > 254 )
				message = message.substr( 0, 254 );

			SendAll( m_Protocol->SEND_W3GS_CHAT_FROM_HOST( fromPID, GetPIDs( ), 16, BYTEARRAY( ), message ) );
		}
		else
		{
			if( message.size( ) > 127 )
				message = message.substr( 0, 127 );

			SendAll( m_Protocol->SEND_W3GS_CHAT_FROM_HOST( fromPID, GetPIDs( ), 32, UTIL_CreateByteArray( (uint32_t)0, false ), message ) );

			if( m_Replay )
				m_Replay->AddChatMessage( fromPID, 32, 0, message );
		}
	}
}

void CBaseGame :: SendAllChat( string message )
{
	SendAllChat( GetHostPID( ), message );
}

void CBaseGame :: SendLocalAdminChat( string message )
{
	if( !m_LocalAdminMessages )
		return;

	// send a message to LAN/local players who are admins
	// at the time of this writing it is only possible for the game owner to meet this criteria because being an admin requires spoof checking
	// this is mainly used for relaying battle.net whispers, chat messages, and emotes to these players

        for( vector<CGamePlayer *> :: iterator i = m_Players.begin( ); i != m_Players.end( ); ++i )
	{
		if( (*i)->GetSpoofed( ) && IsOwner( (*i)->GetName( ) ) && ( UTIL_IsLanIP( (*i)->GetExternalIP( ) ) || UTIL_IsLocalIP( (*i)->GetExternalIP( ), m_GHost->m_LocalAddresses ) ) )
		{
			if( m_VirtualHostPID != 255 )
				SendChat( m_VirtualHostPID, *i, message );
			else
			{
				// make the chat message originate from the recipient since it's not going to be logged to the replay

				SendChat( (*i)->GetPID( ), *i, message );
			}
		}
	}
}

void CBaseGame :: SendAllSlotInfo( )
{
	if( !m_GameLoading && !m_GameLoaded )
	{
		SendAll( m_Protocol->SEND_W3GS_SLOTINFO( m_Slots, m_RandomSeed, m_Map->GetMapLayoutStyle( ), m_Map->GetMapNumPlayers( ) ) );
		m_SlotInfoChanged = false;
	}
}

void CBaseGame :: SendVirtualHostPlayerInfo( CGamePlayer *player )
{
	if( m_VirtualHostPID == 255 )
		return;

	BYTEARRAY IP;
	IP.push_back( 0 );
	IP.push_back( 0 );
	IP.push_back( 0 );
	IP.push_back( 0 );
	Send( player, m_Protocol->SEND_W3GS_PLAYERINFO( m_VirtualHostPID, m_VirtualHostName, IP, IP, string( ) ) );
}

void CBaseGame :: SendFakePlayerInfo( CGamePlayer *player )
{
	if( m_FakePlayerPID == 255 )
		return;

	BYTEARRAY IP;
	IP.push_back( 0 );
	IP.push_back( 0 );
	IP.push_back( 0 );
	IP.push_back( 0 );
	Send( player, m_Protocol->SEND_W3GS_PLAYERINFO( m_FakePlayerPID, "|cFFFF0000CheV", IP, IP, string( ) ) );
}

void CBaseGame :: SendAllActions( )
{
	bool UsingGProxy = false;

        for( vector<CGamePlayer *> :: iterator i = m_Players.begin( ); i != m_Players.end( ); ++i )
	{
		if( (*i)->GetGProxy( ) )
			UsingGProxy = true;
	}

	m_GameTicks += m_Latency;

	if( UsingGProxy )
	{
		// we must send empty actions to non-GProxy++ players
		// GProxy++ will insert these itself so we don't need to send them to GProxy++ players
		// empty actions are used to extend the time a player can use when reconnecting

                for( vector<CGamePlayer *> :: iterator i = m_Players.begin( ); i != m_Players.end( ); ++i )
		{
			if( !(*i)->GetGProxy( ) )
			{
                                for( unsigned char j = 0; j < m_GProxyEmptyActions; ++j )
					Send( *i, m_Protocol->SEND_W3GS_INCOMING_ACTION( queue<CIncomingAction *>( ), 0 ) );
			}
		}

		if( m_Replay )
		{
                        for( unsigned char i = 0; i < m_GProxyEmptyActions; ++i )
				m_Replay->AddTimeSlot( 0, queue<CIncomingAction *>( ) );
		}
	}

	// Warcraft III doesn't seem to respond to empty actions

	/* if( UsingGProxy )
		m_SyncCounter += m_GProxyEmptyActions; */

        ++m_SyncCounter;

	// we aren't allowed to send more than 1460 bytes in a single packet but it's possible we might have more than that many bytes waiting in the queue

	if( !m_Actions.empty( ) )
	{
		// we use a "sub actions queue" which we keep adding actions to until we reach the size limit
		// start by adding one action to the sub actions queue

		queue<CIncomingAction *> SubActions;
		CIncomingAction *Action = m_Actions.front( );
		m_Actions.pop( );
		SubActions.push( Action );
		uint32_t SubActionsLength = Action->GetLength( );

		while( !m_Actions.empty( ) )
		{
			Action = m_Actions.front( );
			m_Actions.pop( );

        		if ( ( *Action->GetAction( ) )[0] == 0x1 )
        		{
                		// if FakePlayer is present then unpause game, otherwise drop triggering player
                		if ( m_FakePlayerPID != 255 && !m_PauseReq )
                		{
					CGamePlayer *Player = GetPlayerFromPID( Action->GetPID( ) );
                		        SendAllChat( "[PeaceMaker] " + Player->GetName( ) + " tried to pause the game. Unpausing." );
					BYTEARRAY CRC;
					BYTEARRAY Action;
					Action.push_back( 2 );
                		        m_Actions.push( new CIncomingAction( m_FakePlayerPID, CRC, Action ) );
					Player->SetPauseTried();
					if( Player->GetPauseTried() == 2 )
						SendChat( Player->GetPID(), "[PeaceMaker] On the next attemp to pause the game you will be punished" );
					if( Player->GetPauseTried() == 3 )
					{
						SendAllChat( "[PeaceMaker] " + Player->GetName( ) + " got punished for trying to gameruin!" );
						m_Pairedpenps.push_back( Pairedpenp( string(), m_GHost->m_DB->Threadedpenp( Player->GetName(), "3rd Pause attemp" , "PeaceMaker", 1, "add" ) ) );
					}
        		        }
		        }

			if ((*Action->GetAction())[0] == 0x6) {
				CGamePlayer *Player = GetPlayerFromPID( Action->GetPID( ) );
				SendAllChat("[PeaceMaker] " + Player->GetName() + " tried to save the game. Justice has served.");
				m_Pairedpenps.push_back( Pairedpenp( string(), m_GHost->m_DB->Threadedpenp( Player->GetName(), "game save" , "PeaceMaker", 1, "add" ) ) );
			}

			// check if adding the next action to the sub actions queue would put us over the limit (1452 because the INCOMING_ACTION and INCOMING_ACTION2 packets use an extra 8 bytes)

			if( SubActionsLength + Action->GetLength( ) > 1452 )
			{
				// we'd be over the limit if we added the next action to the sub actions queue
				// so send everything already in the queue and then clear it out
				// the W3GS_INCOMING_ACTION2 packet handles the overflow but it must be sent *before* the corresponding W3GS_INCOMING_ACTION packet

				SendAll( m_Protocol->SEND_W3GS_INCOMING_ACTION2( SubActions ) );

				if( m_Replay )
					m_Replay->AddTimeSlot2( SubActions );

				while( !SubActions.empty( ) )
				{
					delete SubActions.front( );
					SubActions.pop( );
				}

				SubActionsLength = 0;
			}

			SubActions.push( Action );
			SubActionsLength += Action->GetLength( );
		}

		SendAll( m_Protocol->SEND_W3GS_INCOMING_ACTION( SubActions, m_Latency ) );

		if( m_Replay )
			m_Replay->AddTimeSlot( m_Latency, SubActions );

		while( !SubActions.empty( ) )
		{
			delete SubActions.front( );
			SubActions.pop( );
		}
	}
	else
	{
		SendAll( m_Protocol->SEND_W3GS_INCOMING_ACTION( m_Actions, m_Latency ) );

		if( m_Replay )
			m_Replay->AddTimeSlot( m_Latency, m_Actions );
	}

	uint32_t ActualSendInterval = GetTicks( ) - m_LastActionSentTicks;
	uint32_t ExpectedSendInterval = m_Latency - m_LastActionLateBy;
	m_LastActionLateBy = ActualSendInterval - ExpectedSendInterval;

	if( m_LastActionLateBy > m_Latency )
	{
		// something is going terribly wrong - GHost++ is probably starved of resources
		// print a message because even though this will take more resources it should provide some information to the administrator for future reference
		// other solutions - dynamically modify the latency, request higher priority, terminate other games, ???
		//To causes a performance problem, when the console has to say this 1000 times. Should improve ghost hosting on servers
		CONSOLE_Print( "[GAME: " + m_GameName + "] warning - the latency is " + UTIL_ToString( m_Latency ) + "ms but the last update was late by " + UTIL_ToString( m_LastActionLateBy ) + "ms" );
		m_LastActionLateBy = m_Latency;
	}

	m_LastActionSentTicks = GetTicks( );
}

void CBaseGame :: SendWelcomeMessage( CGamePlayer *player )
{
	// read from motd.txt if available (thanks to zeeg for this addition)

	ifstream in;
	in.open( m_GHost->m_MOTDFile.c_str( ) );

	if( in.fail( ) )
	{
		// default welcome message

		if( m_HCLCommandString.empty( ) )
			SendChat( player, " " );

		SendChat( player, " " );
		SendChat( player, " " );
		SendChat( player, " OH-System                                      http://ohsystem.net/" );
		SendChat( player, "GHost++                                         http://www.codelain.com/" );
		SendChat( player, "-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-" );
		SendChat( player, "     Game Name:                 " + m_GameName );

		if( !m_HCLCommandString.empty( ) )
			SendChat( player, "     HCL Command String:  " + m_HCLCommandString );
	}
	else
	{
		// custom welcome message
		// don't print more than 8 lines

		uint32_t Count = 0;
		string Line;

		while( !in.eof( ) && Count < 8 )
		{
			getline( in, Line );

			if( Line.empty( ) )
			{
				if( !in.eof( ) )
					SendChat( player, " " );
			}
			else
				SendChat( player, Line );

                        ++Count;
		}

		in.close( );
	}


}

void CBaseGame :: SendEndMessage( )
{
	// read from gameover.txt if available

	ifstream in;
	in.open( m_GHost->m_GameOverFile.c_str( ) );

	if( !in.fail( ) )
	{
		// don't print more than 8 lines

		uint32_t Count = 0;
		string Line;

		while( !in.eof( ) && Count < 8 )
		{
			getline( in, Line );

			if( Line.empty( ) )
			{
				if( !in.eof( ) )
					SendAllChat( " " );
			}
			else
				SendAllChat( Line );

                        ++Count;
		}

		in.close( );
	}
}

void CBaseGame :: EventPlayerDeleted( CGamePlayer *player )
{
	CONSOLE_Print( "[GAME: " + m_GameName + "] deleting player [" + player->GetName( ) + "]: " + player->GetLeftReason( ) );

	// remove any queued spoofcheck messages for this player

	if( player->GetWhoisSent( ) && !player->GetJoinedRealm( ).empty( ) && player->GetSpoofedRealm( ).empty( ) )
	{
                for( vector<CBNET *> :: iterator i = m_GHost->m_BNETs.begin( ); i != m_GHost->m_BNETs.end( ); ++i )
		{
			if( (*i)->GetServer( ) == player->GetJoinedRealm( ) )
			{
				// hackhack: there must be a better way to do this

				if( (*i)->GetPasswordHashType( ) == "pvpgn" )
					(*i)->UnqueueChatCommand( "/whereis " + player->GetName( ) );
				else
					(*i)->UnqueueChatCommand( "/whois " + player->GetName( ) );

				(*i)->UnqueueChatCommand( "/w " + player->GetName( ) + " " + m_GHost->m_Language->SpoofCheckByReplying( ) );

			}
		}
	}

	m_LastPlayerLeaveTicks = GetTicks( );

	// in some cases we're forced to send the left message early so don't send it again

	if( player->GetLeftMessageSent( ) )
		return;

	if( m_GameLoaded )
	{
                string MinString = UTIL_ToString( ( m_GameTicks / 1000 ) / 60 );
                string SecString = UTIL_ToString( ( m_GameTicks / 1000 ) % 60 );
                if( MinString.size( ) == 1 )
                        MinString.insert( 0, "0" );
                if( SecString.size( ) == 1 )
                       SecString.insert( 0, "0" );

		m_LogData = m_LogData + "3" + "\t" + "bgm" + "\t" + player->GetName() + "\t" + "-" + "\t" + "-" + "\t" + "-" + "\t" + MinString + ":" + SecString + "\t" + player->GetName() + " " + player->GetLeftReason() + "\n";
		SendAllChat( player->GetName( ) + " " + player->GetLeftReason( ) + "." );
                GAME_Print( 2, MinString, SecString, player->GetName(), "", player->GetLeftReason( ) );
		m_Leavers++;
	}
	if( !m_GameLoaded && !m_GameLoading )
	{
		if( GetSIDFromPID( player->GetPID( ) ) == 11 )
		{
			m_AutoStartPlayers = 10;
			CloseSlot( 11, false );
		}
		m_LogData = m_LogData + "2" + "\t" + "blm" + "\t" + player->GetName() + "\t" + "-" + "\t" + "-" + "\t" + "-" + "\t" + "-" + "\t" + player->GetName() + " " + player->GetLeftReason() + "\n";
		GAME_Print( 3, "", "", player->GetName(), "", player->GetLeftReason() );
	}

	if( player->GetLagging( ) )
		SendAll( m_Protocol->SEND_W3GS_STOP_LAG( player ) );

	// autosave

	if( m_GameLoaded && player->GetLeftCode( ) == PLAYERLEAVE_DISCONNECT && m_AutoSave )
	{
		string SaveGameName = UTIL_FileSafeName( "GHost++ AutoSave " + m_GameName + " (" + player->GetName( ) + ").w3z" );
		CONSOLE_Print( "[GAME: " + m_GameName + "] auto saving [" + SaveGameName + "] before player drop, shortened send interval = " + UTIL_ToString( GetTicks( ) - m_LastActionSentTicks ) );
		BYTEARRAY CRC;
		BYTEARRAY Action;
		Action.push_back( 6 );
		UTIL_AppendByteArray( Action, SaveGameName );
		m_Actions.push( new CIncomingAction( player->GetPID( ), CRC, Action ) );

		// todotodo: with the new latency system there needs to be a way to send a 0-time action

		SendAllActions( );
	}

	if( m_GameLoading && m_LoadInGame )
	{
		// we must buffer player leave messages when using "load in game" to prevent desyncs
		// this ensures the player leave messages are correctly interleaved with the empty updates sent to each player

                for( vector<CGamePlayer *> :: iterator i = m_Players.begin( ); i != m_Players.end( ); ++i )
		{
			if( (*i)->GetFinishedLoading( ) )
			{
				if( !player->GetFinishedLoading( ) )
					Send( *i, m_Protocol->SEND_W3GS_STOP_LAG( player ) );

				Send( *i, m_Protocol->SEND_W3GS_PLAYERLEAVE_OTHERS( player->GetPID( ), player->GetLeftCode( ) ) );
			}
			else
				(*i)->AddLoadInGameData( m_Protocol->SEND_W3GS_PLAYERLEAVE_OTHERS( player->GetPID( ), player->GetLeftCode( ) ) );
		}
	}
	else
	{
		// tell everyone about the player leaving

		SendAll( m_Protocol->SEND_W3GS_PLAYERLEAVE_OTHERS( player->GetPID( ), player->GetLeftCode( ) ) );
	}

	// set the replay's host PID and name to the last player to leave the game
	// this will get overwritten as each player leaves the game so it will eventually be set to the last player

	if( m_Replay && ( m_GameLoading || m_GameLoaded ) )
	{
		m_Replay->SetHostPID( player->GetPID( ) );
		m_Replay->SetHostName( player->GetName( ) );

		// add leave message to replay

		if( m_GameLoading && !m_LoadInGame )
			m_Replay->AddLeaveGameDuringLoading( 1, player->GetPID( ), player->GetLeftCode( ) );
		else
			m_Replay->AddLeaveGame( 1, player->GetPID( ), player->GetLeftCode( ) );
	}

	// abort the countdown if there was one in progress

	if( m_CountDownStarted && !m_GameLoading && !m_GameLoaded )
	{
		SendAllChat( m_GHost->m_Language->CountDownAborted( ) );
		m_CountDownStarted = false;
		m_Balanced = false;
	}

	// abort the votekick

	if( !m_KickVotePlayer.empty( ) )
		SendAllChat( m_GHost->m_Language->VoteKickCancelled( m_KickVotePlayer ) );

	if( !m_GameLoading && !m_GameLoaded )
		m_Denied.push_back( player->GetName( ) + " " + player->GetExternalIPString( ) + " " + UTIL_ToString( GetTime( ) ) );

	m_KickVotePlayer.clear( );
	m_StartedKickVoteTime = 0;
}

void CBaseGame :: EventPlayerDisconnectTimedOut( CGamePlayer *player )
{
	if( player->GetGProxy( ) && m_GameLoaded )
	{
		if( !player->GetGProxyDisconnectNoticeSent( ) )
		{
			SendAllChat( player->GetName( ) + " " + m_GHost->m_Language->HasLostConnectionTimedOutGProxy( ) + "." );
			player->SetGProxyDisconnectNoticeSent( true );
		}

		if( GetTime( ) - player->GetLastGProxyWaitNoticeSentTime( ) >= 20 )
		{
			uint32_t TimeRemaining = ( m_GProxyEmptyActions + 1 ) * 60 - ( GetTime( ) - m_StartedLaggingTime );

			if( TimeRemaining > ( (uint32_t)m_GProxyEmptyActions + 1 ) * 60 )
				TimeRemaining = ( m_GProxyEmptyActions + 1 ) * 60;

			SendAllChat( player->GetPID( ), m_GHost->m_Language->WaitForReconnectSecondsRemain( UTIL_ToString( TimeRemaining ) ) );
			player->SetLastGProxyWaitNoticeSentTime( GetTime( ) );
		}

		return;
	}

	// not only do we not do any timeouts if the game is lagging, we allow for an additional grace period of 10 seconds
	// this is because Warcraft 3 stops sending packets during the lag screen
	// so when the lag screen finishes we would immediately disconnect everyone if we didn't give them some extra time

	if( GetTime( ) - m_LastLagScreenTime >= 10 )
	{
		player->SetDeleteMe( true );
		player->SetLeftReason( m_GHost->m_Language->HasLostConnectionTimedOut( ) );
		player->SetLeftCode( PLAYERLEAVE_DISCONNECT );
		if( player->GetLeavePerc() >= 20 && !player->GetGProxy() && !player->GetSafeDrop( ) )
		{
			player->SetAutoban( true );
		}

		if( !m_GameLoading && !m_GameLoaded )
			OpenSlot( GetSIDFromPID( player->GetPID( ) ), false );
		else
			player->SetLeft( true );
	}
}

void CBaseGame :: EventPlayerDisconnectPlayerError( CGamePlayer *player )
{
	// at the time of this comment there's only one player error and that's when we receive a bad packet from the player
	// since TCP has checks and balances for data corruption the chances of this are pretty slim

	player->SetDeleteMe( true );
	player->SetLeftReason( m_GHost->m_Language->HasLostConnectionPlayerError( player->GetErrorString( ) ) );
	player->SetLeftCode( PLAYERLEAVE_DISCONNECT );

	if( !m_GameLoading && !m_GameLoaded )
		OpenSlot( GetSIDFromPID( player->GetPID( ) ), false );
	else
		player->SetLeft( true );
}

void CBaseGame :: EventPlayerDisconnectSocketError( CGamePlayer *player )
{
	if( player->GetGProxy( ) && m_GameLoaded )
	{
		if( !player->GetGProxyDisconnectNoticeSent( ) )
		{
			SendAllChat( player->GetName( ) + " " + m_GHost->m_Language->HasLostConnectionSocketErrorGProxy( player->GetSocket( )->GetErrorString( ) ) + "." );
			player->SetGProxyDisconnectNoticeSent( true );
		}

		if( GetTime( ) - player->GetLastGProxyWaitNoticeSentTime( ) >= 20 )
		{
			uint32_t TimeRemaining = ( m_GProxyEmptyActions + 1 ) * 60 - ( GetTime( ) - m_StartedLaggingTime );

			if( TimeRemaining > ( (uint32_t)m_GProxyEmptyActions + 1 ) * 60 )
				TimeRemaining = ( m_GProxyEmptyActions + 1 ) * 60;

			SendAllChat( player->GetPID( ), m_GHost->m_Language->WaitForReconnectSecondsRemain( UTIL_ToString( TimeRemaining ) ) );
			player->SetLastGProxyWaitNoticeSentTime( GetTime( ) );
		}

		return;
	}

	player->SetDeleteMe( true );
	player->SetLeftReason( m_GHost->m_Language->HasLostConnectionSocketError( player->GetSocket( )->GetErrorString( ) ) );
	player->SetLeftCode( PLAYERLEAVE_DISCONNECT );

	if( !m_GameLoading && !m_GameLoaded )
		OpenSlot( GetSIDFromPID( player->GetPID( ) ), false );
	else
		player->SetLeft( true );
}

void CBaseGame :: EventPlayerDisconnectConnectionClosed( CGamePlayer *player )
{
	if( player->GetGProxy( ) && m_GameLoaded )
	{
		if( !player->GetGProxyDisconnectNoticeSent( ) )
		{
			SendAllChat( player->GetName( ) + " " + m_GHost->m_Language->HasLostConnectionClosedByRemoteHostGProxy( ) + "." );
			player->SetGProxyDisconnectNoticeSent( true );
		}

		if( GetTime( ) - player->GetLastGProxyWaitNoticeSentTime( ) >= 20 )
		{
			uint32_t TimeRemaining = ( m_GProxyEmptyActions + 1 ) * 60 - ( GetTime( ) - m_StartedLaggingTime );

			if( TimeRemaining > ( (uint32_t)m_GProxyEmptyActions + 1 ) * 60 )
				TimeRemaining = ( m_GProxyEmptyActions + 1 ) * 60;

			SendAllChat( player->GetPID( ), m_GHost->m_Language->WaitForReconnectSecondsRemain( UTIL_ToString( TimeRemaining ) ) );
			player->SetLastGProxyWaitNoticeSentTime( GetTime( ) );
		}

		return;
	}

	player->SetDeleteMe( true );
	player->SetLeftReason( m_GHost->m_Language->HasLostConnectionClosedByRemoteHost( ) );
	player->SetLeftCode( PLAYERLEAVE_DISCONNECT );

	if( !m_GameLoading && !m_GameLoaded )
		OpenSlot( GetSIDFromPID( player->GetPID( ) ), false );
	else
		player->SetLeft( true );
}

void CBaseGame :: EventPlayerJoined( CPotentialPlayer *potential, CIncomingJoinPlayer *joinPlayer )
{
        uint32_t HostCounterID = joinPlayer->GetHostCounter( ) >> 28;
        string JoinedRealm = "garena";

        for( vector<CBNET *> :: iterator i = m_GHost->m_BNETs.begin( ); i != m_GHost->m_BNETs.end( ); ++i )
        {
                if( (*i)->GetHostCounterID( ) == HostCounterID )
                        JoinedRealm = (*i)->GetServer( );
        }

        // check if the player is an admin or root admin on any connected realm for determining reserved status
        // we can't just use the spoof checked realm like in EventPlayerBotCommand because the player hasn't spoof checked yet

        uint32_t Level = 0;
        string LevelName = "unknown";

        // test if player is reserved
        for( vector<CBNET *> :: iterator i = m_GHost->m_BNETs.begin( ); i != m_GHost->m_BNETs.end( ); ++i )
        {
                if( (*i)->GetServer( ) == JoinedRealm )
                {
                        Level = (*i)->IsLevel( joinPlayer->GetName( ) );
                        LevelName = (*i)->GetLevelName( Level );
                        break;
                }
        }

        bool Reserved = IsReserved( joinPlayer->GetName( ) ) || Level > 1 || IsOwner( joinPlayer->GetName( ) );

	// check if player has only digits
	if( Level == 0 && is_digits( joinPlayer->GetName( ) ) )
        {
                CONSOLE_Print( "[GAME: " + m_GameName + "] player [" + joinPlayer->GetName( ) + "|" + potential->GetExternalIPString( ) + "] is trying to join, but he has invalid username (digits only)." );
                potential->Send( m_Protocol->SEND_W3GS_REJECTJOIN( REJECTJOIN_FULL ) );
                potential->SetDeleteMe( true );
                return;
        }

	// check if player is on the deny-vector
	if( Level == 0 && IsDenied( joinPlayer->GetName( ), potential->GetExternalIPString( ) ) )
	{
		CONSOLE_Print( "[GAME: " + m_GameName + "] player [" + joinPlayer->GetName( ) + "|" + potential->GetExternalIPString( ) + "] is trying to join but is denied for this game" );
                potential->Send( m_Protocol->SEND_W3GS_REJECTJOIN( REJECTJOIN_FULL ) );
                potential->SetDeleteMe( true );
                return;
        }

	// check if the new player's name is empty or too long

	string LowerName = joinPlayer->GetName( );
	transform( LowerName.begin( ), LowerName.end( ), LowerName.begin( ), (int(*)(int))tolower );

	if( joinPlayer->GetName( ).empty( ) || joinPlayer->GetName( ).size( ) > 15 || LowerName.find( " " ) != string::npos || LowerName.find( "|" ) != string::npos )
	{
		// autoban the player for spoofing name
		if( !m_GHost->m_BNETs.empty( ) )
			m_GHost->m_Callables.push_back( m_GHost->m_DB->ThreadedBanAdd( m_GHost->m_BNETs[0]->GetServer( ), joinPlayer->GetName( ), potential->GetExternalIPString( ), m_GameName, "PeaceMaker", "attempted to join with spoofed name: " + joinPlayer->GetName( ), 0, "" ) );

		CONSOLE_Print( "[GAME: " + m_GameName + "] player [" + joinPlayer->GetName( ) + "|" + potential->GetExternalIPString( ) + "] is trying to join the game with an invalid name of length " + UTIL_ToString( joinPlayer->GetName( ).size( ) ) );
		m_Denied.push_back( joinPlayer->GetName( ) + " " + potential->GetExternalIPString( ) + " " + UTIL_ToString( GetTime( ) ) );
		potential->Send( m_Protocol->SEND_W3GS_REJECTJOIN( REJECTJOIN_FULL ) );
		potential->SetDeleteMe( true );
		return;
	}

	// check if the new player's name is the same Was the virtual host name

	if( joinPlayer->GetName( ) == m_VirtualHostName )
	{
		CONSOLE_Print( "[GAME: " + m_GameName + "] player [" + joinPlayer->GetName( ) + "|" + potential->GetExternalIPString( ) + "] is trying to join the game with the virtual host name" );
		m_Denied.push_back( joinPlayer->GetName( ) + " " + potential->GetExternalIPString( ) + " " + UTIL_ToString( GetTime( ) ));
		potential->Send( m_Protocol->SEND_W3GS_REJECTJOIN( REJECTJOIN_FULL ) );
		potential->SetDeleteMe( true );
		return;
	}

	// check if the new player's name is already taken

	if( GetPlayerFromName( joinPlayer->GetName( ), false ) )
	{
		CONSOLE_Print( "[GAME: " + m_GameName + "] player [" + joinPlayer->GetName( ) + "|" + potential->GetExternalIPString( ) + "] is trying to join the game but that name is already taken" );
		// SendAllChat( m_GHost->m_Language->TryingToJoinTheGameButTaken( joinPlayer->GetName( ) ) );
		m_Denied.push_back( joinPlayer->GetName( ) + " " + potential->GetExternalIPString( ) + " " + UTIL_ToString( GetTime( ) ) );
		potential->Send( m_Protocol->SEND_W3GS_REJECTJOIN( REJECTJOIN_FULL ) );
		potential->SetDeleteMe( true );
		return;
	}

	// identify their joined realm
	// this is only possible because when we send a game refresh via LAN or battle.net we encode an ID value in the 4 most significant bits of the host counter
	// the client sends the host counter when it joins so we can extract the ID value here
	// note: this is not a replacement for spoof checking since it doesn't verify the player's name and it can be spoofed anyway

	if( JoinedRealm.empty( ) )
	{
		// the player is pretending to join via LAN, which they might or might not be (i.e. it could be spoofed)
		// however, we've been broadcasting a random entry key to the LAN
		// if the player is really on the LAN they'll know the entry key, otherwise they won't
		// or they're very lucky since it's a 32 bit number

		if( joinPlayer->GetEntryKey( ) != m_EntryKey )
		{
			// oops!

			CONSOLE_Print( "[GAME: " + m_GameName + "] player [" + joinPlayer->GetName( ) + "|" + potential->GetExternalIPString( ) + "] is trying to join the game over LAN but used an incorrect entry key" );
			m_Denied.push_back( joinPlayer->GetName( ) + " " + potential->GetExternalIPString( ) + " " + UTIL_ToString( GetTime( ) ) );
			potential->Send( m_Protocol->SEND_W3GS_REJECTJOIN( REJECTJOIN_WRONGPASSWORD ) );
			potential->SetDeleteMe( true );
			return;
		}
	}

	// check if the new player's name is banned but only if bot_banmethod is not 0
	// this is because if bot_banmethod is 0 and we announce the ban here it's possible for the player to be rejected later because the game is full
	// this would allow the player to spam the chat by attempting to join the game multiple times in a row

	if( m_GHost->m_BanMethod != 0 && !Reserved )
	{
                for( vector<CBNET *> :: iterator i = m_GHost->m_BNETs.begin( ); i != m_GHost->m_BNETs.end( ); ++i )
		{
			CDBBan *Ban = (*i)->IsBannedName( joinPlayer->GetName( ) );

			if( Ban )
			{
				if( m_GHost->m_BanMethod == 1 || m_GHost->m_BanMethod == 3 )
				{
					CONSOLE_Print( "[GAME: " + m_GameName + "] player [" + joinPlayer->GetName( ) + "|" + potential->GetExternalIPString( ) + "] is trying to join the game but is banned by name" );
					if( m_IgnoredNames.find( joinPlayer->GetName( ) ) == m_IgnoredNames.end( ) )
					{
						SendAllChat( m_GHost->m_Language->TryingToJoinTheGameButBannedByName( joinPlayer->GetName( ) ) );
						SendAllChat( m_GHost->m_Language->UserWasBannedOnByBecause( Ban->GetServer( ), Ban->GetName( ), Ban->GetDate( ), Ban->GetAdmin( ), Ban->GetReason( ), Ban->GetExpire( ), Ban->GetMonths() ) );
						m_IgnoredNames.insert( joinPlayer->GetName( ) );
					}
					// let banned players "join" the game with an arbitrary PID then immediately close the connection
					// this causes them to be kicked back to the chat channel on battle.net
					m_Denied.push_back( joinPlayer->GetName( ) + " " + potential->GetExternalIPString( ) + " " + UTIL_ToString( GetTime( ) ) );
					vector<CGameSlot> Slots = m_Map->GetSlots( );
					potential->Send( m_Protocol->SEND_W3GS_SLOTINFOJOIN( 1, potential->GetSocket( )->GetPort( ), potential->GetExternalIP( ), Slots, 0, m_Map->GetMapLayoutStyle( ), m_Map->GetMapNumPlayers( ) ) );
					potential->SetDeleteMe( true );
					return;
				}
				break;
			}

			CDBBan *IPBan = (*i)->IsBannedIP( potential->GetExternalIPString( ) );
			// Disabled, created laags if a player join
                        //if( !IPBan && !potential->GetSocket( )->GetHostName( ).empty( ) )
                        //        IPBan = (*i)->IsBannedIP( "h" + potential->GetSocket( )->GetHostName( ) );

			if( IPBan )
			{
				if( m_GHost->m_BanMethod == 2 || m_GHost->m_BanMethod == 3 )
				{
					CONSOLE_Print( "[GAME: " + m_GameName + "] player [" + joinPlayer->GetName( ) + "|" + potential->GetExternalIPString( ) + "] is trying to join the game but is banned by IP address" );

					if( m_IgnoredNames.find( joinPlayer->GetName( ) ) == m_IgnoredNames.end( ) )
					{
						SendAllChat( m_GHost->m_Language->TryingToJoinTheGameButBannedByIP( joinPlayer->GetName( ), potential->GetExternalIPString( ), IPBan->GetName( ) ) );
						SendAllChat( m_GHost->m_Language->UserWasBannedOnByBecause( IPBan->GetServer( ), IPBan->GetName( ), IPBan->GetDate( ), IPBan->GetAdmin( ), IPBan->GetReason( ), IPBan->GetExpire( ), IPBan->GetMonths( ) ) );
						m_IgnoredNames.insert( joinPlayer->GetName( ) );
					}

					// let banned players "join" the game with an arbitrary PID then immediately close the connection
					// this causes them to be kicked back to the chat channel on battle.net
					m_Denied.push_back( joinPlayer->GetName( ) + " " + potential->GetExternalIPString( ) + " " + UTIL_ToString( GetTime( ) ) );
					vector<CGameSlot> Slots = m_Map->GetSlots( );
					potential->Send( m_Protocol->SEND_W3GS_SLOTINFOJOIN( 1, potential->GetSocket( )->GetPort( ), potential->GetExternalIP( ), Slots, 0, m_Map->GetMapLayoutStyle( ), m_Map->GetMapNumPlayers( ) ) );
					potential->SetDeleteMe( true );
					return;
				}
				break;
			}
		}
	}

	if( m_MatchMaking && m_AutoStartPlayers != 0 && !m_Map->GetMapMatchMakingCategory( ).empty( ) && m_Map->GetMapOptions( ) & MAPOPT_FIXEDPLAYERSETTINGS )
	{
		// matchmaking is enabled
		// start a database query to determine the player's score
		// when the query is complete we will call EventPlayerJoinedWithScore

		m_ScoreChecks.push_back( m_GHost->m_DB->ThreadedScoreCheck( m_Map->GetMapMatchMakingCategory( ), joinPlayer->GetName( ), JoinedRealm ) );
		return;
	}

	if( m_GameType == 5 )
	{
		if( !Reserved )
		{
			CONSOLE_Print( "[GAME: " + m_GameName + "] player [" + joinPlayer->GetName( ) + "|" + potential->GetExternalIPString( ) + "] is trying to join but isn't reserved" );
        	        vector<CGameSlot> Slots = m_Map->GetSlots( );
                	potential->Send( m_Protocol->SEND_W3GS_SLOTINFOJOIN( 1, potential->GetSocket( )->GetPort( ), potential->GetExternalIP( ), Slots, 0, m_Map->GetMapLayoutStyle( ), m_Map->GetMapNumPlayers( ) ) );
                	potential->SetDeleteMe( true );
                	return;
		}
		else
			CONSOLE_Print( "[GAME: " + m_GameName + "] player [" + joinPlayer->GetName( ) + "|" + potential->GetExternalIPString( ) + "] joined with " + LevelName + " Status." );
	}

	// try to find a slot

	unsigned char SID = 255;
	unsigned char EnforcePID = 255;
	unsigned char EnforceSID = 0;
	CGameSlot EnforceSlot( 255, 0, 0, 0, 0, 0, 0 );

	if( m_SaveGame )
	{
		// in a saved game we enforce the player layout and the slot layout
		// unfortunately we don't know how to extract the player layout from the saved game so we use the data from a replay instead
		// the !enforcesg command defines the player layout by parsing a replay

                for( vector<PIDPlayer> :: iterator i = m_EnforcePlayers.begin( ); i != m_EnforcePlayers.end( ); ++i )
		{
			if( (*i).second == joinPlayer->GetName( ) )
				EnforcePID = (*i).first;
		}

                for( vector<CGameSlot> :: iterator i = m_EnforceSlots.begin( ); i != m_EnforceSlots.end( ); ++i )
		{
			if( (*i).GetPID( ) == EnforcePID )
			{
				EnforceSlot = *i;
				break;
			}

			EnforceSID++;
		}

		if( EnforcePID == 255 || EnforceSlot.GetPID( ) == 255 || EnforceSID >= m_Slots.size( ) )
		{
			CONSOLE_Print( "[GAME: " + m_GameName + "] player [" + joinPlayer->GetName( ) + "|" + potential->GetExternalIPString( ) + "] is trying to join the game but isn't in the enforced list" );
			m_Denied.push_back( joinPlayer->GetName( ) + " " + potential->GetExternalIPString( ) + " " + UTIL_ToString( GetTime( ) ) );
			potential->Send( m_Protocol->SEND_W3GS_REJECTJOIN( REJECTJOIN_FULL ) );
			potential->SetDeleteMe( true );
			return;
		}

		SID = EnforceSID;
	}
	else
	{
		// try to find an empty slot

		SID = GetEmptySlot( false );

		if( SID == 255 && Reserved )
		{
			// a reserved player is trying to join the game but it's full, try to find a reserved slot

			SID = GetEmptySlot( true );

			if( SID != 255 )
			{
				CGamePlayer *KickedPlayer = GetPlayerFromSID( SID );

				if( KickedPlayer )
				{
					KickedPlayer->SetDeleteMe( true );
					KickedPlayer->SetLeftReason( m_GHost->m_Language->WasKickedForReservedPlayer( joinPlayer->GetName( ) ) );
					KickedPlayer->SetLeftCode( PLAYERLEAVE_LOBBY );

					// send a playerleave message immediately since it won't normally get sent until the player is deleted which is after we send a playerjoin message
					// we don't need to call OpenSlot here because we're about to overwrite the slot data anyway

					SendAll( m_Protocol->SEND_W3GS_PLAYERLEAVE_OTHERS( KickedPlayer->GetPID( ), KickedPlayer->GetLeftCode( ) ) );
					KickedPlayer->SetLeftMessageSent( true );
				}
			}
		}

		if( SID == 255 && IsOwner( joinPlayer->GetName( ) ) )
		{
			// the owner player is trying to join the game but it's full and we couldn't even find a reserved slot, kick the player in the lowest numbered slot
			// updated this to try to find a player slot so that we don't end up kicking a computer

			SID = 0;

                        for( unsigned char i = 0; i < m_Slots.size( ); ++i )
			{
				if( m_Slots[i].GetSlotStatus( ) == SLOTSTATUS_OCCUPIED && m_Slots[i].GetComputer( ) == 0 )
				{
					SID = i;
					break;
				}
			}

			CGamePlayer *KickedPlayer = GetPlayerFromSID( SID );

			if( KickedPlayer )
			{
				KickedPlayer->SetDeleteMe( true );
				KickedPlayer->SetLeftReason( m_GHost->m_Language->WasKickedForOwnerPlayer( joinPlayer->GetName( ) ) );
				KickedPlayer->SetLeftCode( PLAYERLEAVE_LOBBY );

				// send a playerleave message immediately since it won't normally get sent until the player is deleted which is after we send a playerjoin message
				// we don't need to call OpenSlot here because we're about to overwrite the slot data anyway

				SendAll( m_Protocol->SEND_W3GS_PLAYERLEAVE_OTHERS( KickedPlayer->GetPID( ), KickedPlayer->GetLeftCode( ) ) );
				KickedPlayer->SetLeftMessageSent( true );
			}
		}
	}

	if( SID >= m_Slots.size( ) )
	{
		potential->Send( m_Protocol->SEND_W3GS_REJECTJOIN( REJECTJOIN_FULL ) );
		potential->SetDeleteMe( true );
		return;
	}

	// check if the new player's name is banned but only if bot_banmethod is 0
	// this is because if bot_banmethod is 0 we need to wait to announce the ban until now because they could have been rejected because the game was full
	// this would have allowed the player to spam the chat by attempting to join the game multiple times in a row

	if( m_GHost->m_BanMethod == 0 && !Reserved )
	{
                for( vector<CBNET *> :: iterator i = m_GHost->m_BNETs.begin( ); i != m_GHost->m_BNETs.end( ); ++i )
		{
			if( (*i)->GetServer( ) == JoinedRealm )
			{
				CDBBan *Ban = (*i)->IsBannedName( joinPlayer->GetName( ) );

				if( Ban )
				{
					CONSOLE_Print( "[GAME: " + m_GameName + "] player [" + joinPlayer->GetName( ) + "|" + potential->GetExternalIPString( ) + "] is using a banned name" );
					SendAllChat( m_GHost->m_Language->HasBannedName( joinPlayer->GetName( ) ) );
					SendAllChat( m_GHost->m_Language->UserWasBannedOnByBecause( Ban->GetServer( ), Ban->GetName( ), Ban->GetDate( ), Ban->GetAdmin( ), Ban->GetReason( ), Ban->GetExpire( ), Ban->GetMonths( ) ) );
					m_Denied.push_back( joinPlayer->GetName( ) + " " + potential->GetExternalIPString( ) + " " + UTIL_ToString( GetTime( ) ) );
					break;
				}
			}

			CDBBan *Ban = (*i)->IsBannedIP( potential->GetExternalIPString( ) );
			// disabled, created laggs on the join event
			//if( !Ban && !potential->GetSocket( )->GetHostName( ).empty( ) )
			//        Ban = (*i)->IsBannedIP( "h" + potential->GetSocket( )->GetHostName( ) );
			if( Ban )
			{
				CONSOLE_Print( "[GAME: " + m_GameName + "] player [" + joinPlayer->GetName( ) + "|" + potential->GetExternalIPString( ) + "] is using a banned IP address" );
				SendAllChat( m_GHost->m_Language->HasBannedIP( joinPlayer->GetName( ), potential->GetExternalIPString( ), Ban->GetName( ) ) );
				SendAllChat( m_GHost->m_Language->UserWasBannedOnByBecause( Ban->GetServer( ), Ban->GetName( ), Ban->GetDate( ), Ban->GetAdmin( ), Ban->GetReason( ), Ban->GetExpire( ), Ban->GetMonths( ) ) );
				m_Denied.push_back( joinPlayer->GetName( ) + " " + potential->GetExternalIPString( ) + " " + UTIL_ToString( GetTime( ) ) );
				break;
			}
		}
	}

	// we have a slot for the new player
	// make room for them by deleting the virtual host player if we have to

	if( GetSlotsAllocated( ) >= m_Slots.size() - 1 || EnforcePID == m_VirtualHostPID )
		DeleteVirtualHost( );

	// turning the CPotentialPlayer into a CGamePlayer is a bit of a pain because we have to be careful not to close the socket
	// this problem is solved by setting the socket to NULL before deletion and handling the NULL case in the destructor
	// we also have to be careful to not modify the m_Potentials vector since we're currently looping through it

	CONSOLE_Print( "[GAME: " + m_GameName + "] player [" + joinPlayer->GetName( ) + "|" + potential->GetExternalIPString( ) + "] joined the game" );
	CGamePlayer *Player = new CGamePlayer( potential, m_SaveGame ? EnforcePID : GetNewPID( ), JoinedRealm, joinPlayer->GetName( ), joinPlayer->GetInternalIP( ), Reserved );

        // check if this is a protected account
	m_PairedPWChecks.push_back( PairedPWCheck( joinPlayer->GetName( ), m_GHost->m_DB->ThreadedPWCheck( joinPlayer->GetName( ) ) ) );

	// check if the user has a new message
	m_Pairedpms.push_back( Pairedpm( joinPlayer->GetName( ), m_GHost->m_DB->Threadedpm( joinPlayer->GetName( ), string(), 0, string(), "join" ) ) );

	// check win perc
	 m_PairedWPChecks.push_back( PairedWPCheck( joinPlayer->GetName( ), m_GHost->m_DB->ThreadedStatsPlayerSummaryCheck( joinPlayer->GetName( ) ) ) );

	// cookie for reserved players
	if( Level >= 3 )
		Player->SetCookie( 3 );

	// consider LAN players to have already spoof checked since they can't
	// since so many people have trouble with this feature we now use the JoinedRealm to determine LAN status

        if( potential->GetGarenaUser( ) != NULL )
	{

                Player->SetGarenaUser( potential->GetGarenaUser( ) );
                potential->SetGarenaUser( NULL );
	        Player->SetSpoofed( true );
        }

	if( m_GameNoGarena && JoinedRealm == "garena" )
	{
                m_Denied.push_back( joinPlayer->GetName( ) + " " + Player->GetExternalIPString( ) + " " + UTIL_ToString( GetTime( ) ) );
		SendAllChat( "Player ["+joinPlayer->GetName( )+"] got kicked for joining from [Garena]" );
                Player->SetDeleteMe( true );
                Player->SetLeftReason( "was kicked for being banned." );
                Player->SetLeftCode( PLAYERLEAVE_LOBBY );
	        Player->SetLeftMessageSent( true );
		return;
	}

	if( !Player->GetGProxy( ) && Player->GetForcedGproxy( ) )
        {
                m_Denied.push_back( joinPlayer->GetName( ) + " " + Player->GetExternalIPString( ) + " " + UTIL_ToString( GetTime( ) ) );
                SendAllChat( "Player ["+joinPlayer->GetName( )+"] got kicked for joining without Gproxy (but is forced to use it)." );
                Player->SetDeleteMe( true );
                Player->SetLeftReason( "was kicked for not using Gproxy." );
                Player->SetLeftCode( PLAYERLEAVE_LOBBY );
                Player->SetLeftMessageSent( true );
                return;
        }

	GAME_Print( 4, "", "", joinPlayer->GetName(), "", JoinedRealm );

        SendAllChat( "A "+LevelName+" joined [" + joinPlayer->GetName() + "@" + JoinedRealm + "]" );

        // check country code
	// Currently disabled, some querys for the fromcheck need over 50 mseconds which does create some smaller laggs
	// This will be disabled until we have created a decent solution

        // m_PairedFromChecks.push_back( PairedFromCheck( joinPlayer->GetName( ), m_GHost->m_DB->ThreadedFromCheck( Player->GetExternalIPString( ) ) ) );

	if( JoinedRealm.empty( ) )
		Player->SetSpoofed( true );

	Player->SetWhoisShouldBeSent( m_GHost->m_SpoofChecks == 1 || ( m_GHost->m_SpoofChecks == 2 && ( Level >= 5 ) ) );
	m_Players.push_back( Player );
	potential->SetSocket( NULL );
	potential->SetDeleteMe( true );

	if( m_SaveGame )
		m_Slots[SID] = EnforceSlot;
	else
	{
		if( m_Map->GetMapOptions( ) & MAPOPT_CUSTOMFORCES )
			m_Slots[SID] = CGameSlot( Player->GetPID( ), 255, SLOTSTATUS_OCCUPIED, 0, m_Slots[SID].GetTeam( ), m_Slots[SID].GetColour( ), m_Slots[SID].GetRace( ) );
		else
		{
			if( m_Map->GetMapFlags( ) & MAPFLAG_RANDOMRACES )
				m_Slots[SID] = CGameSlot( Player->GetPID( ), 255, SLOTSTATUS_OCCUPIED, 0, 12, 12, SLOTRACE_RANDOM );
			else
				m_Slots[SID] = CGameSlot( Player->GetPID( ), 255, SLOTSTATUS_OCCUPIED, 0, 12, 12, SLOTRACE_RANDOM | SLOTRACE_SELECTABLE );

			// try to pick a team and colour
			// make sure there aren't too many other players already

			unsigned char NumOtherPlayers = 0;

                        for( unsigned char i = 0; i < m_Slots.size( ); ++i )
			{
				if( m_Slots[i].GetSlotStatus( ) == SLOTSTATUS_OCCUPIED && m_Slots[i].GetTeam( ) != 12 )
					NumOtherPlayers++;
			}

			if( NumOtherPlayers < m_Map->GetMapNumPlayers( ) )
			{
				if( SID < m_Map->GetMapNumPlayers( ) )
					m_Slots[SID].SetTeam( SID );
				else
					m_Slots[SID].SetTeam( 0 );

				m_Slots[SID].SetColour( GetNewColour( ) );
			}
		}
	}

	// send slot info to the new player
	// the SLOTINFOJOIN packet also tells the client their assigned PID and that the join was successful

	Player->Send( m_Protocol->SEND_W3GS_SLOTINFOJOIN( Player->GetPID( ), Player->GetSocket( )->GetPort( ), Player->GetExternalIP( ), m_Slots, m_RandomSeed, m_Map->GetMapLayoutStyle( ), m_Map->GetMapNumPlayers( ) ) );

	// send virtual host info and fake player info (if present) to the new player

	SendVirtualHostPlayerInfo( Player );
	SendFakePlayerInfo( Player );

	BYTEARRAY BlankIP;
	BlankIP.push_back( 0 );
	BlankIP.push_back( 0 );
	BlankIP.push_back( 0 );
	BlankIP.push_back( 0 );

        for( vector<CGamePlayer *> :: iterator i = m_Players.begin( ); i != m_Players.end( ); ++i )
	{
		if( !(*i)->GetLeftMessageSent( ) && *i != Player )
		{
			// send info about the new player to every other players
			if( (*i)->GetSocket( ) )
			{
				if( m_GHost->m_HideIPAddresses )
					(*i)->Send( m_Protocol->SEND_W3GS_PLAYERINFO( Player->GetPID( ), Player->GetName( ), BlankIP, BlankIP, GetColoredName( Player->GetName( ) ) ) );
				else
					(*i)->Send( m_Protocol->SEND_W3GS_PLAYERINFO( Player->GetPID( ), Player->GetName( ), Player->GetExternalIP( ), Player->GetInternalIP( ), GetColoredName( Player->GetName( ) ) ) );
			}
				// send info about every other player to the new player

			if( m_GHost->m_HideIPAddresses )
				Player->Send( m_Protocol->SEND_W3GS_PLAYERINFO( (*i)->GetPID( ), (*i)->GetName( ), BlankIP, BlankIP, GetColoredName( (*i)->GetName( ) ) ) );
			else
				Player->Send( m_Protocol->SEND_W3GS_PLAYERINFO( (*i)->GetPID( ), (*i)->GetName( ), (*i)->GetExternalIP( ), (*i)->GetInternalIP( ), GetColoredName( (*i)->GetName( ) ) ) );
		}
	}

	// send a map check packet to the new player

	Player->Send( m_Protocol->SEND_W3GS_MAPCHECK( m_Map->GetMapPath( ), m_Map->GetMapSize( ), m_Map->GetMapInfo( ), m_Map->GetMapCRC( ), m_Map->GetMapSHA1( ) ) );

	// send slot info to everyone, so the new player gets this info twice but everyone else still needs to know the new slot layout

	SendAllSlotInfo( );

	// send a welcome message

	SendWelcomeMessage( Player );

	// if spoof checks are required and we won't automatically spoof check this player then tell them how to spoof check
	// e.g. if automatic spoof checks are disabled, or if automatic spoof checks are done on admins only and this player isn't an admin

	if( m_GHost->m_RequireSpoofChecks && !Player->GetWhoisShouldBeSent( ) )
	{
                for( vector<CBNET *> :: iterator i = m_GHost->m_BNETs.begin( ); i != m_GHost->m_BNETs.end( ); ++i )
		{
			// note: the following (commented out) line of code will crash because calling GetUniqueName( ) twice will result in two different return values
			// and unfortunately iterators are not valid if compared against different containers
			// this comment shall serve as warning to not make this mistake again since it has now been made twice before in GHost++
			// string( (*i)->GetUniqueName( ).begin( ), (*i)->GetUniqueName( ).end( ) )

			BYTEARRAY UniqueName = (*i)->GetUniqueName( );

			if( (*i)->GetServer( ) == JoinedRealm )
				SendChat( Player, m_GHost->m_Language->SpoofCheckByWhispering( string( UniqueName.begin( ), UniqueName.end( ) )  ) );
		}
	}

	// check for multiple IP usage

	if( m_GHost->m_CheckMultipleIPUsage )
	{
		string Others;

                for( vector<CGamePlayer *> :: iterator i = m_Players.begin( ); i != m_Players.end( ); ++i )
		{
			if( Player != *i && Player->GetExternalIPString( ) == (*i)->GetExternalIPString( ) )
			{
				if( Others.empty( ) )
					Others = (*i)->GetName( );
				else
					Others += ", " + (*i)->GetName( );
			}
		}

		if( !Others.empty( ) )
			SendAllChat( m_GHost->m_Language->MultipleIPAddressUsageDetected( joinPlayer->GetName( ), Others ) );
	}

	// abort the countdown if there was one in progress

	if( m_CountDownStarted && !m_GameLoading && !m_GameLoaded )
	{
		SendAllChat( m_GHost->m_Language->CountDownAborted( ) );
		m_CountDownStarted = false;
		m_Balanced = false;
	}

	// auto lock the game

	if( m_GHost->m_AutoLock && !m_Locked && IsOwner( joinPlayer->GetName( ) ) )
	{
		SendAllChat( m_GHost->m_Language->GameLocked( ) );
		m_Locked = true;
	}

	Player->SetSpoofedRealm( JoinedRealm );

	m_LatestSlot = Player->GetPID();

	// check leaveperc
	if( Player->GetLeavePerc( ) >= 60 )
		SendAllChat( "User " + Player->GetName( ) + " got a huge leaver percentage of " + UTIL_ToString( Player->GetLeavePerc( ), 2 ) + "%");
}

void CBaseGame :: EventPlayerJoinedWithScore( CPotentialPlayer *potential, CIncomingJoinPlayer *joinPlayer, double score )
{
        uint32_t HostCounterID = joinPlayer->GetHostCounter( ) >> 28;
        string JoinedRealm;

        // we use an ID value of 0 to denote joining via LAN

        for( vector<CBNET *> :: iterator i = m_GHost->m_BNETs.begin( ); i != m_GHost->m_BNETs.end( ); ++i )
        {
                if( (*i)->GetHostCounterID( ) == HostCounterID )
                        JoinedRealm = (*i)->GetServer( );
        }

	// this function is only called when matchmaking is enabled
	// EventPlayerJoined will be called first in all cases
	// if matchmaking is enabled EventPlayerJoined will start a database query to retrieve the player's score and keep the connection open while we wait
	// when the database query is complete EventPlayerJoinedWithScore will be called

	// check if the new player's name is the same as the virtual host name

	if( joinPlayer->GetName( ) == m_VirtualHostName )
	{
		CONSOLE_Print( "[GAME: " + m_GameName + "] player [" + joinPlayer->GetName( ) + "|" + potential->GetExternalIPString( ) + "] is trying to join the game with the virtual host name" );
		potential->Send( m_Protocol->SEND_W3GS_REJECTJOIN( REJECTJOIN_FULL ) );
		potential->SetDeleteMe( true );
		return;
	}

	// check if the new player's name is already taken

	if( GetPlayerFromName( joinPlayer->GetName( ), false ) )
	{
		CONSOLE_Print( "[GAME: " + m_GameName + "] player [" + joinPlayer->GetName( ) + "|" + potential->GetExternalIPString( ) + "] is trying to join the game but that name is already taken" );
		// SendAllChat( m_GHost->m_Language->TryingToJoinTheGameButTaken( joinPlayer->GetName( ) ) );
		potential->Send( m_Protocol->SEND_W3GS_REJECTJOIN( REJECTJOIN_FULL ) );
		potential->SetDeleteMe( true );
		return;
	}

	// check if the new player's score is within the limits

	if( score > -99999.0 && ( score < m_MinimumScore || score > m_MaximumScore ) )
	{
		CONSOLE_Print( "[GAME: " + m_GameName + "] player [" + joinPlayer->GetName( ) + "|" + potential->GetExternalIPString( ) + "] is trying to join the game but has a rating [" + UTIL_ToString( score, 2 ) + "] outside the limits [" + UTIL_ToString( m_MinimumScore, 2 ) + "] to [" + UTIL_ToString( m_MaximumScore, 2 ) + "]" );
		potential->Send( m_Protocol->SEND_W3GS_REJECTJOIN( REJECTJOIN_FULL ) );
		potential->SetDeleteMe( true );
		return;
	}

	// try to find an empty slot

	unsigned char SID = GetEmptySlot( false );

	// check if the player is an admin or root admin on any connected realm for determining reserved status
	// we can't just use the spoof checked realm like in EventPlayerBotCommand because the player hasn't spoof checked yet

	uint32_t Level = 0;

        for( vector<CBNET *> :: iterator i = m_GHost->m_BNETs.begin( ); i != m_GHost->m_BNETs.end( ); ++i )
	{
                if( (*i)->GetServer( ) == JoinedRealm )
                {
                        Level = (*i)->IsLevel( joinPlayer->GetName( ) );
                        break;
                }
	}

	if( SID == 255 )
	{
		// no empty slot found, time to do some matchmaking!
		// note: the database code uses a score of -100000 to denote "no score"

		if( m_GHost->m_MatchMakingMethod == 0 )
		{
			// method 0: don't do any matchmaking
			// that was easy!
		}
		else if( m_GHost->m_MatchMakingMethod == 1 )
		{
			// method 1: furthest score method
			// calculate the average score of all players in the game
			// then kick the player with the score furthest from that average (or a player without a score)
			// this ensures that the players' scores will tend to converge as players join the game

			double AverageScore = 0.0;
			uint32_t PlayersScored = 0;

			if( score > -99999.0 )
			{
				AverageScore = score;
				PlayersScored = 1;
			}

                        for( vector<CGamePlayer *> :: iterator i = m_Players.begin( ); i != m_Players.end( ); ++i )
			{
				if( (*i)->GetScore( ) > -99999.0 )
				{
					AverageScore += (*i)->GetScore( );
					PlayersScored++;
				}
			}

			if( PlayersScored > 0 )
				AverageScore /= PlayersScored;

			// calculate the furthest player from the average

			CGamePlayer *FurthestPlayer = NULL;

                        for( vector<CGamePlayer *> :: iterator i = m_Players.begin( ); i != m_Players.end( ); ++i )
			{
				if( !FurthestPlayer || (*i)->GetScore( ) < -99999.0 || abs( (*i)->GetScore( ) - AverageScore ) > abs( FurthestPlayer->GetScore( ) - AverageScore ) )
					FurthestPlayer = *i;
			}

			if( !FurthestPlayer )
			{
				// this should be impossible

				CONSOLE_Print( "[GAME: " + m_GameName + "] player [" + joinPlayer->GetName( ) + "|" + potential->GetExternalIPString( ) + "] is trying to join the game but no furthest player was found (this should be impossible)" );
				potential->Send( m_Protocol->SEND_W3GS_REJECTJOIN( REJECTJOIN_FULL ) );
				potential->SetDeleteMe( true );
				return;
			}

			// kick the new player if they have the furthest score

			if( score < -99999.0 || abs( score - AverageScore ) > abs( FurthestPlayer->GetScore( ) - AverageScore ) )
			{
				if( score < -99999.0 )
					CONSOLE_Print( "[GAME: " + m_GameName + "] player [" + joinPlayer->GetName( ) + "|" + potential->GetExternalIPString( ) + "] is trying to join the game but has the furthest rating [N/A] from the average [" + UTIL_ToString( AverageScore, 2 ) + "]" );
				else
					CONSOLE_Print( "[GAME: " + m_GameName + "] player [" + joinPlayer->GetName( ) + "|" + potential->GetExternalIPString( ) + "] is trying to join the game but has the furthest rating [" + UTIL_ToString( score, 2 ) + "] from the average [" + UTIL_ToString( AverageScore, 2 ) + "]" );

				potential->Send( m_Protocol->SEND_W3GS_REJECTJOIN( REJECTJOIN_FULL ) );
				potential->SetDeleteMe( true );
				return;
			}

			// kick the furthest player

			SID = GetSIDFromPID( FurthestPlayer->GetPID( ) );
			FurthestPlayer->SetDeleteMe( true );

			if( FurthestPlayer->GetScore( ) < -99999.0 )
				FurthestPlayer->SetLeftReason( m_GHost->m_Language->WasKickedForHavingFurthestScore( "N/A", UTIL_ToString( AverageScore, 2 ) ) );
			else
				FurthestPlayer->SetLeftReason( m_GHost->m_Language->WasKickedForHavingFurthestScore( UTIL_ToString( FurthestPlayer->GetScore( ), 2 ), UTIL_ToString( AverageScore, 2 ) ) );

			FurthestPlayer->SetLeftCode( PLAYERLEAVE_LOBBY );

			// send a playerleave message immediately since it won't normally get sent until the player is deleted which is after we send a playerjoin message
			// we don't need to call OpenSlot here because we're about to overwrite the slot data anyway

			SendAll( m_Protocol->SEND_W3GS_PLAYERLEAVE_OTHERS( FurthestPlayer->GetPID( ), FurthestPlayer->GetLeftCode( ) ) );
			FurthestPlayer->SetLeftMessageSent( true );

			if( FurthestPlayer->GetScore( ) < -99999.0 )
				SendAllChat( m_GHost->m_Language->PlayerWasKickedForFurthestScore( FurthestPlayer->GetName( ), "N/A", UTIL_ToString( AverageScore, 2 ) ) );
			else
				SendAllChat( m_GHost->m_Language->PlayerWasKickedForFurthestScore( FurthestPlayer->GetName( ), UTIL_ToString( FurthestPlayer->GetScore( ), 2 ), UTIL_ToString( AverageScore, 2 ) ) );
		}
		else if( m_GHost->m_MatchMakingMethod == 2 )
		{
			// method 2: lowest score method
			// kick the player with the lowest score (or a player without a score)

			CGamePlayer *LowestPlayer = NULL;

                        for( vector<CGamePlayer *> :: iterator i = m_Players.begin( ); i != m_Players.end( ); ++i )
			{
				if( !LowestPlayer || (*i)->GetScore( ) < -99999.0 || (*i)->GetScore( ) < LowestPlayer->GetScore( ) )
					LowestPlayer = *i;
			}

			if( !LowestPlayer )
			{
				// this should be impossible

				CONSOLE_Print( "[GAME: " + m_GameName + "] player [" + joinPlayer->GetName( ) + "|" + potential->GetExternalIPString( ) + "] is trying to join the game but no lowest player was found (this should be impossible)" );
				potential->Send( m_Protocol->SEND_W3GS_REJECTJOIN( REJECTJOIN_FULL ) );
				potential->SetDeleteMe( true );
				return;
			}

			// kick the new player if they have the lowest score

			if( score < -99999.0 || score < LowestPlayer->GetScore( ) )
			{
				if( score < -99999.0 )
					CONSOLE_Print( "[GAME: " + m_GameName + "] player [" + joinPlayer->GetName( ) + "|" + potential->GetExternalIPString( ) + "] is trying to join the game but has the lowest rating [N/A]" );
				else
					CONSOLE_Print( "[GAME: " + m_GameName + "] player [" + joinPlayer->GetName( ) + "|" + potential->GetExternalIPString( ) + "] is trying to join the game but has the lowest rating [" + UTIL_ToString( score, 2 ) + "]" );

				potential->Send( m_Protocol->SEND_W3GS_REJECTJOIN( REJECTJOIN_FULL ) );
				potential->SetDeleteMe( true );
				return;
			}

			// kick the lowest player

			SID = GetSIDFromPID( LowestPlayer->GetPID( ) );
			LowestPlayer->SetDeleteMe( true );

			if( LowestPlayer->GetScore( ) < -99999.0 )
				LowestPlayer->SetLeftReason( m_GHost->m_Language->WasKickedForHavingLowestScore( "N/A" ) );
			else
				LowestPlayer->SetLeftReason( m_GHost->m_Language->WasKickedForHavingLowestScore( UTIL_ToString( LowestPlayer->GetScore( ), 2 ) ) );

			LowestPlayer->SetLeftCode( PLAYERLEAVE_LOBBY );

			// send a playerleave message immediately since it won't normally get sent until the player is deleted which is after we send a playerjoin message
			// we don't need to call OpenSlot here because we're about to overwrite the slot data anyway

			SendAll( m_Protocol->SEND_W3GS_PLAYERLEAVE_OTHERS( LowestPlayer->GetPID( ), LowestPlayer->GetLeftCode( ) ) );
			LowestPlayer->SetLeftMessageSent( true );

			if( LowestPlayer->GetScore( ) < -99999.0 )
				SendAllChat( m_GHost->m_Language->PlayerWasKickedForLowestScore( LowestPlayer->GetName( ), "N/A" ) );
			else
				SendAllChat( m_GHost->m_Language->PlayerWasKickedForLowestScore( LowestPlayer->GetName( ), UTIL_ToString( LowestPlayer->GetScore( ), 2 ) ) );
		}
	}

	if( SID >= m_Slots.size( ) )
	{
		potential->Send( m_Protocol->SEND_W3GS_REJECTJOIN( REJECTJOIN_FULL ) );
		potential->SetDeleteMe( true );
		return;
	}

	// we have a slot for the new player
	// make room for them by deleting the virtual host player if we have to

	if( GetSlotsOccupied( ) >= m_Slots.size() - 1 )
		DeleteVirtualHost( );

	// identify their joined realm
	// this is only possible because when we send a game refresh via LAN or battle.net we encode an ID value in the 4 most significant bits of the host counter
	// the client sends the host counter when it joins so we can extract the ID value here
	// note: this is not a replacement for spoof checking since it doesn't verify the player's name and it can be spoofed anywas

	if( JoinedRealm.empty( ) )
	{
		// the player is pretending to join via LAN, which they might or might not be (i.e. it could be spoofed)
		// however, we've been broadcasting a random entry key to the LAN
		// if the player is really on the LAN they'll know the entry key, otherwise they won't
		// or they're very lucky since it's a 32 bit number

		if( joinPlayer->GetEntryKey( ) != m_EntryKey )
		{
			// oops!

			CONSOLE_Print( "[GAME: " + m_GameName + "] player [" + joinPlayer->GetName( ) + "|" + potential->GetExternalIPString( ) + "] is trying to join the game over LAN but used an incorrect entry key" );
			potential->Send( m_Protocol->SEND_W3GS_REJECTJOIN( REJECTJOIN_WRONGPASSWORD ) );
			potential->SetDeleteMe( true );
			return;
		}
	}

	// turning the CPotentialPlayer into a CGamePlayer is a bit of a pain because we have to be careful not to close the socket
	// this problem is solved by setting the socket to NULL before deletion and handling the NULL case in the destructor
	// we also have to be careful to not modify the m_Potentials vector since we're currently looping through it

	CONSOLE_Print( "[GAME: " + m_GameName + "] player [" + joinPlayer->GetName( ) + "|" + potential->GetExternalIPString( ) + "] joined the game" );
	CGamePlayer *Player = new CGamePlayer( potential, GetNewPID( ), JoinedRealm, joinPlayer->GetName( ), joinPlayer->GetInternalIP( ), false );

	// consider LAN players to have already spoof checked since they can't
	// since so many people have trouble with this feature we now use the JoinedRealm to determine LAN status

	if( JoinedRealm.empty( ) )
		Player->SetSpoofed( true );

	Player->SetWhoisShouldBeSent( m_GHost->m_SpoofChecks == 1 || ( m_GHost->m_SpoofChecks == 2 && Level >= 5 ) );
	Player->SetScore( score );
	m_Players.push_back( Player );
	potential->SetSocket( NULL );
	potential->SetDeleteMe( true );
	m_Slots[SID] = CGameSlot( Player->GetPID( ), 255, SLOTSTATUS_OCCUPIED, 0, m_Slots[SID].GetTeam( ), m_Slots[SID].GetColour( ), m_Slots[SID].GetRace( ) );

	// send slot info to the new player
	// the SLOTINFOJOIN packet also tells the client their assigned PID and that the join was successful

	Player->Send( m_Protocol->SEND_W3GS_SLOTINFOJOIN( Player->GetPID( ), Player->GetSocket( )->GetPort( ), Player->GetExternalIP( ), m_Slots, m_RandomSeed, m_Map->GetMapLayoutStyle( ), m_Map->GetMapNumPlayers( ) ) );

	// send virtual host info and fake player info (if present) to the new player

	SendVirtualHostPlayerInfo( Player );
	SendFakePlayerInfo( Player );

	BYTEARRAY BlankIP;
	BlankIP.push_back( 0 );
	BlankIP.push_back( 0 );
	BlankIP.push_back( 0 );
	BlankIP.push_back( 0 );

        for( vector<CGamePlayer *> :: iterator i = m_Players.begin( ); i != m_Players.end( ); ++i )
	{
		if( !(*i)->GetLeftMessageSent( ) && *i != Player )
		{
			// send info about the new player to every other player

			string ColoredName;
			if( (*i)->GetSocket( ) )
			{
				if( m_GHost->m_HideIPAddresses )
					(*i)->Send( m_Protocol->SEND_W3GS_PLAYERINFO( Player->GetPID( ), Player->GetName( ), BlankIP, BlankIP, GetColoredName( Player->GetName( ) ) ) );
				else
					(*i)->Send( m_Protocol->SEND_W3GS_PLAYERINFO( Player->GetPID( ), Player->GetName( ), Player->GetExternalIP( ), Player->GetInternalIP( ), GetColoredName( Player->GetName( ) ) ) );
			}

			// send info about every other player to the new player

			if( m_GHost->m_HideIPAddresses )
				Player->Send( m_Protocol->SEND_W3GS_PLAYERINFO( (*i)->GetPID( ), (*i)->GetName( ), BlankIP, BlankIP, GetColoredName( (*i)->GetName( ) ) ) );
			else
				Player->Send( m_Protocol->SEND_W3GS_PLAYERINFO( (*i)->GetPID( ), (*i)->GetName( ), (*i)->GetExternalIP( ), (*i)->GetInternalIP( ), GetColoredName( (*i)->GetName( ) ) ) );
		}
	}

	// send a map check packet to the new player

	Player->Send( m_Protocol->SEND_W3GS_MAPCHECK( m_Map->GetMapPath( ), m_Map->GetMapSize( ), m_Map->GetMapInfo( ), m_Map->GetMapCRC( ), m_Map->GetMapSHA1( ) ) );

	// send slot info to everyone, so the new player gets this info twice but everyone else still needs to know the new slot layout

	SendAllSlotInfo( );

	// send a welcome message

	SendWelcomeMessage( Player );

	// if spoof checks are required and we won't automatically spoof check this player then tell them how to spoof check
	// e.g. if automatic spoof checks are disabled, or if automatic spoof checks are done on admins only and this player isn't an admin

	if( m_GHost->m_RequireSpoofChecks && !Player->GetWhoisShouldBeSent( ) )
	{
                for( vector<CBNET *> :: iterator i = m_GHost->m_BNETs.begin( ); i != m_GHost->m_BNETs.end( ); ++i )
		{
			// note: the following (commented out) line of code will crash because calling GetUniqueName( ) twice will result in two different return values
			// and unfortunately iterators are not valid if compared against different containers
			// this comment shall serve as warning to not make this mistake again since it has now been made twice before in GHost++
			// string( (*i)->GetUniqueName( ).begin( ), (*i)->GetUniqueName( ).end( ) )

			BYTEARRAY UniqueName = (*i)->GetUniqueName( );

			if( (*i)->GetServer( ) == JoinedRealm )
				SendChat( Player, m_GHost->m_Language->SpoofCheckByWhispering( string( UniqueName.begin( ), UniqueName.end( ) )  ) );
		}
	}

	if( score < -99999.0 )
		SendAllChat( m_GHost->m_Language->PlayerHasScore( joinPlayer->GetName( ), "N/A" ) );
	else
		SendAllChat( m_GHost->m_Language->PlayerHasScore( joinPlayer->GetName( ), UTIL_ToString( score, 2 ) ) );

	uint32_t PlayersScored = 0;
	uint32_t PlayersNotScored = 0;
	double AverageScore = 0.0;
	double MinScore = 0.0;
	double MaxScore = 0.0;
	bool Found = false;

        for( vector<CGamePlayer *> :: iterator i = m_Players.begin( ); i != m_Players.end( ); ++i )
	{
		if( !(*i)->GetLeftMessageSent( ) )
		{
			if( (*i)->GetScore( ) < -99999.0 )
				PlayersNotScored++;
			else
			{
				PlayersScored++;
				AverageScore += (*i)->GetScore( );

				if( !Found || (*i)->GetScore( ) < MinScore )
					MinScore = (*i)->GetScore( );

				if( !Found || (*i)->GetScore( ) > MaxScore )
					MaxScore = (*i)->GetScore( );

				Found = true;
			}
		}
	}

	double Spread = MaxScore - MinScore;
	SendAllChat( m_GHost->m_Language->RatedPlayersSpread( UTIL_ToString( PlayersScored ), UTIL_ToString( PlayersScored + PlayersNotScored ), UTIL_ToString( (uint32_t)Spread ) ) );

	// check for multiple IP usage

	if( m_GHost->m_CheckMultipleIPUsage )
	{
		string Others;

                for( vector<CGamePlayer *> :: iterator i = m_Players.begin( ); i != m_Players.end( ); ++i )
		{
			if( Player != *i && Player->GetExternalIPString( ) == (*i)->GetExternalIPString( ) )
			{
				if( Others.empty( ) )
					Others = (*i)->GetName( );
				else
					Others += ", " + (*i)->GetName( );
			}
		}

		if( !Others.empty( ) )
			SendAllChat( m_GHost->m_Language->MultipleIPAddressUsageDetected( joinPlayer->GetName( ), Others ) );
	}

	// abort the countdown if there was one in progress

	if( m_CountDownStarted && !m_GameLoading && !m_GameLoaded )
	{
		SendAllChat( m_GHost->m_Language->CountDownAborted( ) );
		m_CountDownStarted = false;
                m_Balanced = false;
	}

	// auto lock the game

	if( m_GHost->m_AutoLock && !m_Locked && IsOwner( joinPlayer->GetName( ) ) )
	{
		SendAllChat( m_GHost->m_Language->GameLocked( ) );
		m_Locked = true;
	}

	// balance the slots

	if( m_AutoStartPlayers != 0 && GetNumHumanPlayers( ) == m_AutoStartPlayers )
		BalanceSlots( );

	Player->SetSpoofedRealm( JoinedRealm );
}

void CBaseGame :: EventPlayerLeft( CGamePlayer *player, uint32_t reason )
{
	// this function is only called when a player leave packet is received, not when there's a socket error, kick, etc...

	player->SetDeleteMe( true );

	if( reason == PLAYERLEAVE_GPROXY )
		player->SetLeftReason( m_GHost->m_Language->WasUnrecoverablyDroppedFromGProxy( ) );
	else
	{
		player->SetLeftReason( m_GHost->m_Language->HasLeftVoluntarily( ) );
                if( player->GetLeavePerc() >= 20 && !player->GetSafeDrop( ) )
                {
                        player->SetAutoban( true );
                }

	}

	player->SetLeftCode( PLAYERLEAVE_LOST );

	if( !m_GameLoading && !m_GameLoaded )
	{
		OpenSlot( GetSIDFromPID( player->GetPID( ) ), false );
		m_Balanced = false;
	}
	else
		player->SetLeft( true );
}

void CBaseGame :: EventPlayerLoaded( CGamePlayer *player )
{
	CONSOLE_Print( "[GAME: " + m_GameName + "] player [" + player->GetName( ) + "] finished loading in " + UTIL_ToString( (float)( player->GetFinishedLoadingTicks( ) - m_StartedLoadingTicks ) / 1000, 2 ) + " seconds" );

	player->SetTimeActive( GetTime( ) );
	if( m_LoadInGame )
	{
		// send any buffered data to the player now
		// see the Update function for more information about why we do this
		// this includes player loaded messages, game updates, and player leave messages

		queue<BYTEARRAY> *LoadInGameData = player->GetLoadInGameData( );

		while( !LoadInGameData->empty( ) )
		{
			Send( player, LoadInGameData->front( ) );
			LoadInGameData->pop( );
		}

		// start the lag screen for the new player

		bool FinishedLoading = true;

                for( vector<CGamePlayer *> :: iterator i = m_Players.begin( ); i != m_Players.end( ); ++i )
		{
			FinishedLoading = (*i)->GetFinishedLoading( );

			if( !FinishedLoading )
				break;
		}

		if( !FinishedLoading )
			Send( player, m_Protocol->SEND_W3GS_START_LAG( m_Players, true ) );

		// remove the new player from previously loaded players' lag screens

                for( vector<CGamePlayer *> :: iterator i = m_Players.begin( ); i != m_Players.end( ); ++i )
		{
			if( *i != player && (*i)->GetFinishedLoading( ) )
				Send( *i, m_Protocol->SEND_W3GS_STOP_LAG( player ) );
		}

		// send a chat message to previously loaded players

                for( vector<CGamePlayer *> :: iterator i = m_Players.begin( ); i != m_Players.end( ); ++i )
		{
			if( *i != player && (*i)->GetFinishedLoading( ) )
				SendChat( *i, m_GHost->m_Language->PlayerFinishedLoading( player->GetName( ) ) );
		}

		if( !FinishedLoading )
			SendChat( player, m_GHost->m_Language->PleaseWaitPlayersStillLoading( ) );
	}
	else
		SendAll( m_Protocol->SEND_W3GS_GAMELOADED_OTHERS( player->GetPID( ) ) );
}

bool CBaseGame :: EventPlayerAction( CGamePlayer *player, CIncomingAction *action )
{
	if( ( !m_GameLoaded && !m_GameLoading ) || action->GetLength( ) > 1027 )
	{
		delete action;
		return false;
	}
 if( !action->GetAction( )->empty( ) )
  {
    BYTEARRAY *ActionData = action->GetAction( );
    unsigned int i = 0;

    uint32_t PacketLength = ActionData->size( );

    if( PacketLength > 0 )
    {
      bool PlayerActivity = false;   // used for AFK detection

      uint32_t ActionSize = 0;
      uint32_t n = 0;
      uint32_t p = 0;

      unsigned int CurrentID = 255;
      unsigned int PreviousID = 255;

      bool Failed = false;

      while( n < PacketLength && !Failed )
      {
        PreviousID = CurrentID;
        CurrentID = (*ActionData)[n];

        switch ( CurrentID )
        {
            case 0x00 : Failed = true; break;
            case 0x01 : n += 1; break;
            case 0x02 : n += 1; break;
            case 0x03 : n += 2; break;
            case 0x04 : n += 1; break;
            case 0x05 : n += 1; break;
            case 0x06 :
              Failed = true;
              while( n < PacketLength )
              {
                if((*ActionData)[n] == 0)
                {
                  Failed = false;
                  break;
                }
                ++n;
              }
              ++n;

              // notify everyone that a player is saving the game
               CONSOLE_Print( "[GAME: " + m_GameName + "] player [" + player->GetName( ) + "] is saving the game" );
               SendAllChat( m_GHost->m_Language->PlayerIsSavingTheGame( player->GetName( ) ) );
            break;
            case 0x07 : n += 5; break;
            case 0x08 : Failed = true; break;
            case 0x09 : Failed = true; break;
            case 0x10 : n += 15; PlayerActivity = true; break;
            case 0x11 : n += 23; PlayerActivity = true; break;
            case 0x12 : n += 31; PlayerActivity = true; break;
            case 0x13 : n += 39; PlayerActivity = true; break;
            case 0x14 : n += 44; PlayerActivity = true; break;
            case 0x15 : Failed = true; break;
            case 0x16 :
            case 0x17 :
              if( n + 4 > PacketLength )
                Failed = true;
              else
              {
                unsigned char i = (*ActionData)[n+2];
                if( (*ActionData)[n+3] != 0x00 || i > 16 )
                  Failed = true;
                else
                  n += (4 + (i * 8));
              }
              PlayerActivity = true;
            break;
            case 0x18 : n += 3; break;
            case 0x19 : n += 13; break;
            case 0x1A : n += 1; break;
            case 0x1B : n += 10; PlayerActivity = true; break;
            case 0x1C : n += 10; PlayerActivity = true; break;
            case 0x1D : n += 9; break;
            case 0x1E : n += 6; PlayerActivity = true; break;
            case 0x1F : Failed = true; break;
            case 0x20 : Failed = true; break;
            case 0x21 : n += 9; break;

            case 0x50 : n += 6; break;
            case 0x51 :
              n += 10;

               SendAllChat( "[PeaceMaker] Player [" + player->GetName( ) + "] prevented from transferring gold/lumber." );
	       SendAllChat( "[PeaceMaker] Player [" + player->GetName( ) + "] got permanently banned for hacking!" );
               player->SetDeleteMe( true );
               player->SetLeftReason( "was kicked by host" );
               player->SetLeftCode( PLAYERLEAVE_LOST );
	       m_GHost->m_Callables.push_back( m_GHost->m_DB->ThreadedBanAdd( m_GHost->m_BNETs[0]->GetServer( ), player->GetName( ), player->GetExternalIPString( ), m_GameName, "PeaceMaker", "MapHack", 0, "" ) );

               delete action;
               return false;
            break;
            case 0x52 : Failed = true; break;
            case 0x53 : Failed = true; break;
            case 0x54 : Failed = true; break;
            case 0x55 : Failed = true; break;
            case 0x56 : Failed = true; break;
            case 0x57 : Failed = true; break;
            case 0x58 : Failed = true; break;
            case 0x59 : Failed = true; break;
            case 0x5A : Failed = true; break;
            case 0x5B : Failed = true; break;
            case 0x5C : Failed = true; break;
            case 0x5D : Failed = true; break;
            case 0x5E : Failed = true; break;
            case 0x5F : Failed = true; break;
            case 0x60 :
            {
              n += 9;
              unsigned int j = 0;
              Failed = true;
              while( n < PacketLength && j < 128 )
              {
                if((*ActionData)[n] == 0)
                {
                  Failed = false;
                  break;
                }
                ++n;
                ++j;
              }
              ++n;
            }
            break;
            case 0x61 : n += 1; PlayerActivity = true; break;
            case 0x62 : n += 13; break;
            case 0x63 : n += 9; break;
            case 0x64 : n += 9; break;
            case 0x65 : n += 9; break;
            case 0x66 : n += 1; PlayerActivity = true; break;
            case 0x67 : n += 1; PlayerActivity = true; break;
            case 0x68 : n += 13; break;
            case 0x69 : n += 17; break;
            case 0x6A : n += 17; break;
            case 0x6B : // used by W3MMD
            {
              ++n;
              unsigned int j = 0;
              while( n < PacketLength && j < 3 )
              {
                if((*ActionData)[n] == 0) {
                  ++j;
                }
                ++n;
              }
              n += 4;
            }
            break;
            case 0x6C : Failed = true; break;
            case 0x6D : Failed = true; break;
            case 0x6E : Failed = true; break;
            case 0x6F : Failed = true; break;
            case 0x70 : Failed = true; break;
            case 0x71 : Failed = true; break;
            case 0x72 : Failed = true; break;
            case 0x73 : Failed = true; break;
            case 0x74 : Failed = true; break;
            case 0x75 : n += 2; break;
            default:
              Failed = true;
        }

        ActionSize = n - p;
        p = n;

        if( ActionSize > 1024 )
          Failed = true;
      }


      if( PlayerActivity )
        player->SetTimeActive( GetTime( ) );
    }
  }
  m_Actions.push( action );

	return true;
}

void CBaseGame :: EventPlayerKeepAlive( CGamePlayer *player, uint32_t checkSum )
{
	if( !m_GameLoaded )
		return;

	// check for desyncs
	// however, it's possible that not every player has sent a checksum for this frame yet
	// first we verify that we have enough checksums to work with otherwise we won't know exactly who desynced

        for( vector<CGamePlayer *> :: iterator i = m_Players.begin( ); i != m_Players.end( ); ++i )
	{
		if( !(*i)->GetDeleteMe( ) && (*i)->GetCheckSums( )->empty( ) )
			return;
	}

	// now we check for desyncs since we know that every player has at least one checksum waiting

	bool FoundPlayer = false;
	uint32_t FirstCheckSum = 0;

        for( vector<CGamePlayer *> :: iterator i = m_Players.begin( ); i != m_Players.end( ); ++i )
	{
		if( !(*i)->GetDeleteMe( ) )
		{
			FoundPlayer = true;
			FirstCheckSum = (*i)->GetCheckSums( )->front( );
			break;
		}
	}

	if( !FoundPlayer )
		return;

	bool AddToReplay = true;

        for( vector<CGamePlayer *> :: iterator i = m_Players.begin( ); i != m_Players.end( ); ++i )
	{
		if( !(*i)->GetDeleteMe( ) && (*i)->GetCheckSums( )->front( ) != FirstCheckSum )
		{
			CONSOLE_Print( "[GAME: " + m_GameName + "] desync detected" );
			SendAllChat( m_GHost->m_Language->DesyncDetected( ) );

			// try to figure out who desynced
			// this is complicated by the fact that we don't know what the correct game state is so we let the players vote
			// put the players into bins based on their game state

			map<uint32_t, vector<unsigned char> > Bins;

                        for( vector<CGamePlayer *> :: iterator j = m_Players.begin( ); j != m_Players.end( ); ++j )
			{
				if( !(*j)->GetDeleteMe( ) )
					Bins[(*j)->GetCheckSums( )->front( )].push_back( (*j)->GetPID( ) );
			}

			uint32_t StateNumber = 1;
			map<uint32_t, vector<unsigned char> > :: iterator LargestBin = Bins.begin( );
			bool Tied = false;

                        for( map<uint32_t, vector<unsigned char> > :: iterator j = Bins.begin( ); j != Bins.end( ); ++j )
			{
				if( (*j).second.size( ) > (*LargestBin).second.size( ) )
				{
					LargestBin = j;
					Tied = false;
				}
				else if( j != LargestBin && (*j).second.size( ) == (*LargestBin).second.size( ) )
					Tied = true;

				string Players;

                                for( vector<unsigned char> :: iterator k = (*j).second.begin( ); k != (*j).second.end( ); ++k )
				{
					CGamePlayer *Player = GetPlayerFromPID( *k );

					if( Player )
					{
						if( Players.empty( ) )
							Players = Player->GetName( );
						else
							Players += ", " + Player->GetName( );
					}
				}

				SendAllChat( m_GHost->m_Language->PlayersInGameState( UTIL_ToString( StateNumber ), Players ) );
                                ++StateNumber;
			}

			FirstCheckSum = (*LargestBin).first;

			if( Tied )
			{
				// there is a tie, which is unfortunate
				// the most common way for this to happen is with a desync in a 1v1 situation
				// this is not really unsolvable since the game shouldn't continue anyway so we just kick both players
				// in a 2v2 or higher the chance of this happening is very slim
				// however, we still kick every player because it's not fair to pick one or another group
				// todotodo: it would be possible to split the game at this point and create a "new" game for each game state

				CONSOLE_Print( "[GAME: " + m_GameName + "] can't kick desynced players because there is a tie, kicking all players instead" );
				StopPlayers( m_GHost->m_Language->WasDroppedDesync( ) );
				AddToReplay = false;
			}
			else
			{
				CONSOLE_Print( "[GAME: " + m_GameName + "] kicking desynced players" );

                                for( map<uint32_t, vector<unsigned char> > :: iterator j = Bins.begin( ); j != Bins.end( ); ++j )
				{
					// kick players who are NOT in the largest bin
					// examples: suppose there are 10 players
					// the most common case will be 9v1 (e.g. one player desynced and the others were unaffected) and this will kick the single outlier
					// another (very unlikely) possibility is 8v1v1 or 8v2 and this will kick both of the outliers, regardless of whether their game states match

					if( (*j).first != (*LargestBin).first )
					{
                                                for( vector<unsigned char> :: iterator k = (*j).second.begin( ); k != (*j).second.end( ); ++k )
						{
							CGamePlayer *Player = GetPlayerFromPID( *k );

							if( Player )
							{
								Player->SetSafeDrop( true );
								Player->SetDeleteMe( true );
								Player->SetLeftReason( m_GHost->m_Language->WasDroppedDesync( ) );
								Player->SetLeftCode( PLAYERLEAVE_LOST );
							}
						}
					}
				}
			}

			// don't continue looking for desyncs, we already found one!

			break;
		}
	}

        for( vector<CGamePlayer *> :: iterator i = m_Players.begin( ); i != m_Players.end( ); ++i )
	{
		if( !(*i)->GetDeleteMe( ) )
			(*i)->GetCheckSums( )->pop( );
	}

	// add checksum to replay

	/* if( m_Replay && AddToReplay )
		m_Replay->AddCheckSum( FirstCheckSum ); */
}

void CBaseGame :: EventPlayerChatToHost( CGamePlayer *player, CIncomingChatPlayer *chatPlayer )
{
	if( chatPlayer->GetFromPID( ) == player->GetPID( ) )
	{
		if( chatPlayer->GetType( ) == CIncomingChatPlayer :: CTH_MESSAGE || chatPlayer->GetType( ) == CIncomingChatPlayer :: CTH_MESSAGEEXTRA )
		{
			 // AFK detection
			player->SetTimeActive( GetTime( ) );

			// relay the chat message to other players

			bool Relay = !player->GetMuted( );
			BYTEARRAY ExtraFlags = chatPlayer->GetExtraFlags( );

			// calculate timestamp

			string MinString = UTIL_ToString( ( m_GameTicks / 1000 ) / 60 );
			string SecString = UTIL_ToString( ( m_GameTicks / 1000 ) % 60 );

			if( MinString.size( ) == 1 )
				MinString.insert( 0, "0" );

			if( SecString.size( ) == 1 )
				SecString.insert( 0, "0" );

			if( !ExtraFlags.empty( ) )
			{
				unsigned char SID = GetSIDFromPID( chatPlayer->GetFromPID() );
				string slot = UTIL_ToString( GetSIDFromPID( chatPlayer->GetFromPID() ) );
				unsigned char fteam;
				fteam = m_Slots[SID].GetTeam();
				unsigned char sid = GetSIDFromPID( chatPlayer->GetFromPID() );

				if( !m_GameLoaded )
					Relay = false;
				else if( ExtraFlags[0] != 0 && ExtraFlags[0] != 2 ) {
					string curteam = "";
					if( fteam == 0 ) curteam = "Sentinel";
					else if( fteam == 1 ) curteam = "Scourge";

					m_LogData = m_LogData + "5" + "\t" + curteam + "\t" + player->GetName() + "\t" + "-" + "\t" + "-" + "\t" + "-" + "\t" + MinString + ":" + SecString + "\t" + chatPlayer->GetMessage() + "\n";
					if( fteam == 0 )
						GAME_Print( 5, MinString, SecString, player->GetName(), "", chatPlayer->GetMessage() );
					else
						GAME_Print( 6, MinString, SecString, player->GetName(), "", chatPlayer->GetMessage() );
				}
				else if( ExtraFlags[0] == 0 )
				{
					// this is an ingame [All] message, print it to the console

					CONSOLE_Print( "[GAME: " + m_GameName + "] (" + MinString + ":" + SecString + ") [All] [" + player->GetName( ) + "]: " + chatPlayer->GetMessage( ) );
					m_LogData = m_LogData + "5" + "\t" + "all" + "\t" +  player->GetName() + "\t" + "-" + "\t" + "-" + "\t" + "-" + "\t" + MinString + ":" + SecString + "\t" + chatPlayer->GetMessage() + "\n";
					GAME_Print( 7, MinString, SecString, player->GetName( ), "", chatPlayer->GetMessage( ) );
					// don't relay ingame messages targeted for all players if we're currently muting all
					// note that commands will still be processed even when muting all because we only stop relaying the messages, the rest of the function is unaffected

					if( m_MuteAll )
						Relay = false;
				}
				else if( ExtraFlags[0] == 2 )
				{
					// this is an ingame [Obs/Ref] message, print it to the console

					CONSOLE_Print( "[GAME: " + m_GameName + "] (" + MinString + ":" + SecString + ") [Obs/Ref] [" + player->GetName( ) + "]: " + chatPlayer->GetMessage( ) );
					GAME_Print( 8, MinString, SecString, player->GetName( ), "", chatPlayer->GetMessage( ) );
				}

				if( Relay )
				{
					// add chat message to replay
					// this includes allied chat and private chat from both teams as long as it was relayed

					if( m_Replay )
						m_Replay->AddChatMessage( chatPlayer->GetFromPID( ), chatPlayer->GetFlag( ), UTIL_ByteArrayToUInt32( chatPlayer->GetExtraFlags( ), false ), chatPlayer->GetMessage( ) );
				}
			}
			else
			{
				if( m_GameLoading || m_GameLoaded )
					Relay = false;
				else
				{
					// this is a lobby message, print it to the console

					CONSOLE_Print( "[GAME: " + m_GameName + "] [Lobby] [" + player->GetName( ) + "]: " + chatPlayer->GetMessage( ) );
					// Hide password protection
					string LMessage = chatPlayer->GetMessage( );
					if( LMessage.substr( 1, 2 ) != "pw" || LMessage.substr( 1, 4 ) != "pass" || LMessage.substr( 1, 8 ) != "password" )
					{
						m_LogData = m_LogData + "1" + "\t" + "l" + "\t" +  player->GetName() + "\t" + "-" + "\t" + "-" + "\t" + "-" + "\t" + "-" + "\t" + chatPlayer->GetMessage() + "\n";
						GAME_Print( 9, MinString, SecString, player->GetName( ), "", chatPlayer->GetMessage( ) );
					}
					if( m_MuteLobby )
						Relay = false;
				}
			}

			// handle bot commands

			string Message = chatPlayer->GetMessage( );

			if( Message == "?trigger" )
				SendChat( player, m_GHost->m_Language->CommandTrigger( string( 1, m_GHost->m_CommandTrigger ) ) );
			else if( !Message.empty( ) && Message[0] == m_GHost->m_CommandTrigger )
			{
				// extract the command trigger, the command, and the payload
				// e.g. "!say hello world" -> command: "say", payload: "hello world"

				string Command;
				string Payload;
				string :: size_type PayloadStart = Message.find( " " );

				if( PayloadStart != string :: npos )
				{
					Command = Message.substr( 1, PayloadStart - 1 );
					Payload = Message.substr( PayloadStart + 1 );
				}
				else
					Command = Message.substr( 1 );

				transform( Command.begin( ), Command.end( ), Command.begin( ), (int(*)(int))tolower );

				// don't allow EventPlayerBotCommand to veto a previous instruction to set Relay to false
				// so if Relay is already false (e.g. because the player is muted) then it cannot be forced back to true here

				if( EventPlayerBotCommand( player, Command, Payload ) )
					Relay = false;
			}

			if( Relay )
			{
				BYTEARRAY PIDs = chatPlayer->GetToPIDs( );
				for( unsigned int i = 0; i < PIDs.size( ); ++i )
				{
					CGamePlayer *toPlayer = GetPlayerFromPID( PIDs[i] );

					if( toPlayer )
					{
						if( !toPlayer->GetIsIgnoring( player->GetName( ) ) && !player->GetIsIgnoring( toPlayer->GetName( ) ) )
							Send( toPlayer, m_Protocol->SEND_W3GS_CHAT_FROM_HOST( chatPlayer->GetFromPID( ), Silence(chatPlayer->GetToPIDs( )), chatPlayer->GetFlag( ), chatPlayer->GetExtraFlags( ), chatPlayer->GetMessage( ) ) );
					}
				}
			}
		}
		else if( chatPlayer->GetType( ) == CIncomingChatPlayer :: CTH_TEAMCHANGE && !m_CountDownStarted )
			EventPlayerChangeTeam( player, chatPlayer->GetByte( ) );
		else if( chatPlayer->GetType( ) == CIncomingChatPlayer :: CTH_COLOURCHANGE && !m_CountDownStarted )
			EventPlayerChangeColour( player, chatPlayer->GetByte( ) );
		else if( chatPlayer->GetType( ) == CIncomingChatPlayer :: CTH_RACECHANGE && !m_CountDownStarted )
			EventPlayerChangeRace( player, chatPlayer->GetByte( ) );
		else if( chatPlayer->GetType( ) == CIncomingChatPlayer :: CTH_HANDICAPCHANGE && !m_CountDownStarted )
			EventPlayerChangeHandicap( player, chatPlayer->GetByte( ) );
	}
}

bool CBaseGame :: EventPlayerBotCommand( CGamePlayer *player, string command, string payload )
{
	// return true if the command itself should be hidden from other players

	return false;
}

void CBaseGame :: EventPlayerChangeTeam( CGamePlayer *player, unsigned char team )
{
	// player is requesting a team change

	if( m_SaveGame )
		return;

	if( m_Map->GetMapOptions( ) & MAPOPT_CUSTOMFORCES )
	{
		unsigned char oldSID = GetSIDFromPID( player->GetPID( ) );
		unsigned char newSID = GetEmptySlot( team, player->GetPID( ) );
		SwapSlots( oldSID, newSID );
	}
	else
	{
		if( team > 12 )
			return;

		if( team == 12 )
		{
			if( m_Map->GetMapObservers( ) != MAPOBS_ALLOWED && m_Map->GetMapObservers( ) != MAPOBS_REFEREES )
				return;
		}
		else
		{
			if( team >= m_Map->GetMapNumPlayers( ) )
				return;

			// make sure there aren't too many other players already

			unsigned char NumOtherPlayers = 0;

                        for( unsigned char i = 0; i < m_Slots.size( ); ++i )
			{
				if( m_Slots[i].GetSlotStatus( ) == SLOTSTATUS_OCCUPIED && m_Slots[i].GetTeam( ) != 12 && m_Slots[i].GetPID( ) != player->GetPID( ) )
                                        ++NumOtherPlayers;
			}

			if( NumOtherPlayers >= m_Map->GetMapNumPlayers( ) )
				return;
		}

		unsigned char SID = GetSIDFromPID( player->GetPID( ) );

		if( SID < m_Slots.size( ) )
		{
			m_Slots[SID].SetTeam( team );

			if( team == 12 )
			{
				// if they're joining the observer team give them the observer colour

				m_Slots[SID].SetColour( 12 );
			}
			else if( m_Slots[SID].GetColour( ) == 12 )
			{
				// if they're joining a regular team give them an unused colour

				m_Slots[SID].SetColour( GetNewColour( ) );
			}

			SendAllSlotInfo( );
		}
	}
}

void CBaseGame :: EventPlayerChangeColour( CGamePlayer *player, unsigned char colour )
{
	// player is requesting a colour change

	if( m_SaveGame )
		return;

	if( m_Map->GetMapOptions( ) & MAPOPT_FIXEDPLAYERSETTINGS )
		return;

	if( colour > 11 )
		return;

	unsigned char SID = GetSIDFromPID( player->GetPID( ) );

	if( SID < m_Slots.size( ) )
	{
		// make sure the player isn't an observer

		if( m_Slots[SID].GetTeam( ) == 12 )
			return;

		ColourSlot( SID, colour );
	}
}

void CBaseGame :: EventPlayerChangeRace( CGamePlayer *player, unsigned char race )
{
	// player is requesting a race change

	if( m_SaveGame )
		return;

	if( m_Map->GetMapOptions( ) & MAPOPT_FIXEDPLAYERSETTINGS )
		return;

	if( m_Map->GetMapFlags( ) & MAPFLAG_RANDOMRACES )
		return;

	if( race != SLOTRACE_HUMAN && race != SLOTRACE_ORC && race != SLOTRACE_NIGHTELF && race != SLOTRACE_UNDEAD && race != SLOTRACE_RANDOM )
		return;

	unsigned char SID = GetSIDFromPID( player->GetPID( ) );

	if( SID < m_Slots.size( ) )
	{
		m_Slots[SID].SetRace( race | SLOTRACE_SELECTABLE );
		SendAllSlotInfo( );
	}
}

void CBaseGame :: EventPlayerChangeHandicap( CGamePlayer *player, unsigned char handicap )
{
	// player is requesting a handicap change

	if( m_SaveGame )
		return;

	if( m_Map->GetMapOptions( ) & MAPOPT_FIXEDPLAYERSETTINGS )
		return;

	if( handicap != 50 && handicap != 60 && handicap != 70 && handicap != 80 && handicap != 90 && handicap != 100 )
		return;

	unsigned char SID = GetSIDFromPID( player->GetPID( ) );

	if( SID < m_Slots.size( ) )
	{
		m_Slots[SID].SetHandicap( handicap );
		SendAllSlotInfo( );
	}
}

void CBaseGame :: EventPlayerDropRequest( CGamePlayer *player )
{
	// todotodo: check that we've waited the full 45 seconds

	if( m_Lagging )
	{
		CONSOLE_Print( "[GAME: " + m_GameName + "] player [" + player->GetName( ) + "] voted to drop laggers" );
		SendAllChat( m_GHost->m_Language->PlayerVotedToDropLaggers( player->GetName( ) ) );

		// check if at least half the players voted to drop

		uint32_t Votes = 0;

                for( vector<CGamePlayer *> :: iterator i = m_Players.begin( ); i != m_Players.end( ); ++i )
		{
			if( (*i)->GetDropVote( ) )
                                ++Votes;
		}

		if( (float)Votes / m_Players.size( ) > 0.49 )
			StopLaggers( m_GHost->m_Language->LaggedOutDroppedByVote( ) );
	}
}

void CBaseGame :: EventPlayerMapSize( CGamePlayer *player, CIncomingMapSize *mapSize )
{
	if( m_GameLoading || m_GameLoaded )
		return;

	// todotodo: the variable names here are confusing due to extremely poor design on my part

	uint32_t MapSize = UTIL_ByteArrayToUInt32( m_Map->GetMapSize( ), false );

	if( mapSize->GetSizeFlag( ) != 1 || mapSize->GetMapSize( ) != MapSize )
	{
		// the player doesn't have the map

		if( m_GHost->m_AllowDownloads != 0 )
		{
			string *MapData = m_Map->GetMapData( );

			if( !MapData->empty( ) )
			{
				if( m_GHost->m_AllowDownloads == 1 || ( m_GHost->m_AllowDownloads == 2 && player->GetDownloadAllowed( ) ) )
				{
					if( !player->GetDownloadStarted( ) && mapSize->GetSizeFlag( ) == 1 )
					{
						// inform the client that we are willing to send the map

						CONSOLE_Print( "[GAME: " + m_GameName + "] map download started for player [" + player->GetName( ) + "]" );
						Send( player, m_Protocol->SEND_W3GS_STARTDOWNLOAD( GetHostPID( ) ) );
						player->SetDownloadStarted( true );
						player->SetStartedDownloadingTicks( GetTicks( ) );
					}
					else
						player->SetLastMapPartAcked( mapSize->GetMapSize( ) );
				}
			}
			else
			{
				player->SetDeleteMe( true );
				player->SetLeftReason( "doesn't have the map and there is no local copy of the map to send" );
				player->SetLeftCode( PLAYERLEAVE_LOBBY );
				OpenSlot( GetSIDFromPID( player->GetPID( ) ), false );
			}
		}
		else
		{
			player->SetDeleteMe( true );
			player->SetLeftReason( "doesn't have the map and map downloads are disabled" );
			player->SetLeftCode( PLAYERLEAVE_LOBBY );
			OpenSlot( GetSIDFromPID( player->GetPID( ) ), false );
		}
	}
	else
	{
		if( player->GetDownloadStarted( ) && !player->GetDownloadFinished( ) )
		{
			// calculate download rate

			float Seconds = (float)( GetTicks( ) - player->GetStartedDownloadingTicks( ) ) / 1000;
			float Rate = (float)MapSize / 1024 / Seconds;
			CONSOLE_Print( "[GAME: " + m_GameName + "] map download finished for player [" + player->GetName( ) + "] in " + UTIL_ToString( Seconds, 1 ) + " seconds" );
			SendAllChat( m_GHost->m_Language->PlayerDownloadedTheMap( player->GetName( ), UTIL_ToString( Seconds, 1 ), UTIL_ToString( Rate, 1 ) ) );
			player->SetDownloadFinished( true );
			player->SetFinishedDownloadingTime( GetTime( ) );

			// add to database

			m_GHost->m_Callables.push_back( m_GHost->m_DB->ThreadedDownloadAdd( m_Map->GetMapPath( ), MapSize, player->GetName( ), player->GetExternalIPString( ), player->GetSpoofed( ) ? 1 : 0, player->GetSpoofedRealm( ), GetTicks( ) - player->GetStartedDownloadingTicks( ) ) );
		}
	}

	unsigned char NewDownloadStatus = (unsigned char)( (float)mapSize->GetMapSize( ) / MapSize * 100 );
	unsigned char SID = GetSIDFromPID( player->GetPID( ) );

	if( NewDownloadStatus > 100 )
		NewDownloadStatus = 100;

	if( SID < m_Slots.size( ) )
	{
		// only send the slot info if the download status changed

		if( m_Slots[SID].GetDownloadStatus( ) != NewDownloadStatus )
		{
			m_Slots[SID].SetDownloadStatus( NewDownloadStatus );

			// we don't actually send the new slot info here
			// this is an optimization because it's possible for a player to download a map very quickly
			// if we send a new slot update for every percentage change in their download status it adds up to a lot of data
			// instead, we mark the slot info as "out of date" and update it only once in awhile (once per second when this comment was made)

			m_SlotInfoChanged = true;
		}
	}
}

void CBaseGame :: EventPlayerPongToHost( CGamePlayer *player, uint32_t pong )
{
	// autokick players with excessive pings but only if they're not reserved and we've received at least 3 pings from them
	// also don't kick anyone if the game is loading or loaded - this could happen because we send pings during loading but we stop sending them after the game is loaded
	// see the Update function for where we send pings

	if( !m_GameLoading && !m_GameLoaded && !player->GetDeleteMe( ) && !player->GetReserved( ) && player->GetNumPings( ) >= 3 && player->GetPing( m_GHost->m_LCPings ) > m_GHost->m_AutoKickPing )
	{
		// send a chat message because we don't normally do so when a player leaves the lobby

		SendAllChat( m_GHost->m_Language->AutokickingPlayerForExcessivePing( player->GetName( ), UTIL_ToString( player->GetPing( m_GHost->m_LCPings ) ) ) );
		player->SetDeleteMe( true );
		player->SetLeftReason( "was autokicked for excessive ping of " + UTIL_ToString( player->GetPing( m_GHost->m_LCPings ) ) );
		player->SetLeftCode( PLAYERLEAVE_LOBBY );
		OpenSlot( GetSIDFromPID( player->GetPID( ) ), false );
	}
}

void CBaseGame :: EventGameRefreshed( string server )
{
	if( m_RefreshRehosted )
	{
		// we're not actually guaranteed this refresh was for the rehosted game and not the previous one
		// but since we unqueue game refreshes when rehosting, the only way this can happen is due to network delay
		// it's a risk we're willing to take but can result in a false positive here

		SendAllChat( m_GHost->m_Language->RehostWasSuccessful( ) );
		m_RefreshRehosted = false;
	}
}

void CBaseGame :: EventGameStarted( )
{
	GAME_Print( 10, "", "", "System", "", "started loading with " + UTIL_ToString( GetNumHumanPlayers( ) ) + " players." );
	CONSOLE_Print( "[GAME: " + m_GameName + "] started loading with " + UTIL_ToString( GetNumHumanPlayers( ) ) + " players" );

	// encode the HCL command string in the slot handicaps
	// here's how it works:
	//  the user inputs a command string to be sent to the map
	//  it is almost impossible to send a message from the bot to the map so we encode the command string in the slot handicaps
	//  this works because there are only 6 valid handicaps but Warcraft III allows the bot to set up to 256 handicaps
	//  we encode the original (unmodified) handicaps in the new handicaps and use the remaining space to store a short message
	//  only occupied slots deliver their handicaps to the map and we can send one character (from a list) per handicap
	//  when the map finishes loading, assuming it's designed to use the HCL system, it checks if anyone has an invalid handicap
	//  if so, it decodes the message from the handicaps and restores the original handicaps using the encoded values
	//  the meaning of the message is specific to each map and the bot doesn't need to understand it
	//  e.g. you could send game modes, of rounds, level to start on, anything you want as long as it fits in the limited space available
	//  note: if you attempt to use the HCL system on a map that does not support HCL the bot will drastically modify the handicaps
	//  since the map won't automatically restore the original handicaps in this case your game will be ruined

	if( !m_HCLCommandString.empty( ) )
	{
		if( m_HCLCommandString.size( ) <= GetSlotsOccupied( ) )
		{
			string HCLChars = "abcdefghijklmnopqrstuvwxyz0123456789 -=,.";

			if( m_HCLCommandString.find_first_not_of( HCLChars ) == string :: npos )
			{
				unsigned char EncodingMap[256];
				unsigned char j = 0;

                                for( uint32_t i = 0; i < 256; ++i )
				{
					// the following 7 handicap values are forbidden

					if( j == 0 || j == 50 || j == 60 || j == 70 || j == 80 || j == 90 || j == 100 )
                                                ++j;

                                        EncodingMap[i] = j++;
				}

				unsigned char CurrentSlot = 0;

                                for( string :: iterator si = m_HCLCommandString.begin( ); si != m_HCLCommandString.end( ); ++si )
				{
					while( m_Slots[CurrentSlot].GetSlotStatus( ) != SLOTSTATUS_OCCUPIED )
                                                ++CurrentSlot;

					unsigned char HandicapIndex = ( m_Slots[CurrentSlot].GetHandicap( ) - 50 ) / 10;
					unsigned char CharIndex = HCLChars.find( *si );
					m_Slots[CurrentSlot++].SetHandicap( EncodingMap[HandicapIndex + CharIndex * 6] );
				}

				SendAllSlotInfo( );
				CONSOLE_Print( "[GAME: " + m_GameName + "] successfully encoded HCL command string [" + m_HCLCommandString + "]" );
			}
			else
				CONSOLE_Print( "[GAME: " + m_GameName + "] encoding HCL command string [" + m_HCLCommandString + "] failed because it contains invalid characters" );
		}
		else
			CONSOLE_Print( "[GAME: " + m_GameName + "] encoding HCL command string [" + m_HCLCommandString + "] failed because there aren't enough occupied slots" );
	}

	// send a final slot info update if necessary
	// this typically won't happen because we prevent the !start command from completing while someone is downloading the map
	// however, if someone uses !start force while a player is downloading the map this could trigger
	// this is because we only permit slot info updates to be flagged when it's just a change in download status, all others are sent immediately
	// it might not be necessary but let's clean up the mess anyway

	if( m_SlotInfoChanged )
		SendAllSlotInfo( );

	m_StartedLoadingTicks = GetTicks( );
	m_LastLagScreenResetTime = GetTime( );
	m_GameLoading = true;

	// since we use a fake countdown to deal with leavers during countdown the COUNTDOWN_START and COUNTDOWN_END packets are sent in quick succession
	// send a start countdown packet

	SendAll( m_Protocol->SEND_W3GS_COUNTDOWN_START( ) );

	// remove the virtual host player

	DeleteVirtualHost( );

	// send an end countdown packet

	SendAll( m_Protocol->SEND_W3GS_COUNTDOWN_END( ) );

	// send a game loaded packet for the fake player (if present)

	if( m_FakePlayerPID != 255 )
		SendAll( m_Protocol->SEND_W3GS_GAMELOADED_OTHERS( m_FakePlayerPID ) );

	// record the starting number of players

	m_StartPlayers = GetNumHumanPlayers( );

	// close the listening socket

	delete m_Socket;
	m_Socket = NULL;

	// delete any potential players that are still hanging around

        for( vector<CPotentialPlayer *> :: iterator i = m_Potentials.begin( ); i != m_Potentials.end( ); ++i )
		delete *i;

	m_Potentials.clear( );

	// set initial values for replay

	if( m_Replay )
	{
                for( vector<CGamePlayer *> :: iterator i = m_Players.begin( ); i != m_Players.end( ); ++i )
			m_Replay->AddPlayer( (*i)->GetPID( ), (*i)->GetName( ) );

		if( m_FakePlayerPID != 255 )
			m_Replay->AddPlayer( m_FakePlayerPID, "FakePlayer" );

		m_Replay->SetSlots( m_Slots );
		m_Replay->SetRandomSeed( m_RandomSeed );
		m_Replay->SetSelectMode( m_Map->GetMapLayoutStyle( ) );
		m_Replay->SetStartSpotCount( m_Map->GetMapNumPlayers( ) );

		if( m_SaveGame )
		{
			uint32_t MapGameType = MAPGAMETYPE_SAVEDGAME;

			if( m_GameState == GAME_PRIVATE )
				MapGameType |= MAPGAMETYPE_PRIVATEGAME;

			m_Replay->SetMapGameType( MapGameType );
		}
		else
		{
			uint32_t MapGameType = m_Map->GetMapGameType( );
			MapGameType |= MAPGAMETYPE_UNKNOWN0;

			if( m_GameState == GAME_PRIVATE )
				MapGameType |= MAPGAMETYPE_PRIVATEGAME;

			m_Replay->SetMapGameType( MapGameType );
		}

		if( !m_Players.empty( ) )
		{
			// this might not be necessary since we're going to overwrite the replay's host PID and name everytime a player leaves

			m_Replay->SetHostPID( m_Players[0]->GetPID( ) );
			m_Replay->SetHostName( m_Players[0]->GetName( ) );
		}
	}

	// build a stat string for use when saving the replay
	// we have to build this now because the map data is going to be deleted

	BYTEARRAY StatString;
	UTIL_AppendByteArray( StatString, m_Map->GetMapGameFlags( ) );
	StatString.push_back( 0 );
	UTIL_AppendByteArray( StatString, m_Map->GetMapWidth( ) );
	UTIL_AppendByteArray( StatString, m_Map->GetMapHeight( ) );
	UTIL_AppendByteArray( StatString, m_Map->GetMapCRC( ) );
	UTIL_AppendByteArray( StatString, m_Map->GetMapPath( ) );
	UTIL_AppendByteArray( StatString, "GHost++" );
	StatString.push_back( 0 );
	UTIL_AppendByteArray( StatString, m_Map->GetMapSHA1( ) );		// note: in replays generated by Warcraft III it stores 20 zeros for the SHA1 instead of the real thing
	StatString = UTIL_EncodeStatString( StatString );
	m_StatString = string( StatString.begin( ), StatString.end( ) );

	// delete the map data

	delete m_Map;
	m_Map = NULL;

	if( m_LoadInGame )
	{
		// buffer all the player loaded messages
		// this ensures that every player receives the same set of player loaded messages in the same order, even if someone leaves during loading
		// if someone leaves during loading we buffer the leave message to ensure it gets sent in the correct position but the player loaded message wouldn't get sent if we didn't buffer it now

                for( vector<CGamePlayer *> :: iterator i = m_Players.begin( ); i != m_Players.end( ); ++i )
		{
                        for( vector<CGamePlayer *> :: iterator j = m_Players.begin( ); j != m_Players.end( ); ++j )
				(*j)->AddLoadInGameData( m_Protocol->SEND_W3GS_GAMELOADED_OTHERS( (*i)->GetPID( ) ) );
		}
	}

	// move the game to the games in progress vector

	m_GHost->m_CurrentGame = NULL;
	m_GHost->m_Games.push_back( this );

	// and finally reenter battle.net chat

        for( vector<CBNET *> :: iterator i = m_GHost->m_BNETs.begin( ); i != m_GHost->m_BNETs.end( ); ++i )
	{
		(*i)->QueueGameUncreate( );
		(*i)->QueueEnterChat( );
	}
}

void CBaseGame :: EventGameLoaded( )
{
        // UPDATE STATUS
        m_GHost->m_Callables.push_back( m_GHost->m_DB->Threadedgs( m_ChatID, string(), 2, uint32_t() ) );

	CONSOLE_Print( "[GAME: " + m_GameName + "] finished loading with " + UTIL_ToString( GetNumHumanPlayers( ) ) + " players" );

	// send shortest, longest, and personal load times to each player

	CGamePlayer *Shortest = NULL;
	CGamePlayer *Longest = NULL;

        for( vector<CGamePlayer *> :: iterator i = m_Players.begin( ); i != m_Players.end( ); ++i )
	{
		if( !Shortest || (*i)->GetFinishedLoadingTicks( ) < Shortest->GetFinishedLoadingTicks( ) )
			Shortest = *i;

		if( !Longest || (*i)->GetFinishedLoadingTicks( ) > Longest->GetFinishedLoadingTicks( ) )
			Longest = *i;
	}

	if( Shortest && Longest )
	{
		SendAllChat( m_GHost->m_Language->ShortestLoadByPlayer( Shortest->GetName( ), UTIL_ToString( (float)( Shortest->GetFinishedLoadingTicks( ) - m_StartedLoadingTicks ) / 1000, 2 ) ) );
		SendAllChat( m_GHost->m_Language->LongestLoadByPlayer( Longest->GetName( ), UTIL_ToString( (float)( Longest->GetFinishedLoadingTicks( ) - m_StartedLoadingTicks ) / 1000, 2 ) ) );
	}

        for( vector<CGamePlayer *> :: iterator i = m_Players.begin( ); i != m_Players.end( ); ++i )
		SendChat( *i, m_GHost->m_Language->YourLoadingTimeWas( UTIL_ToString( (float)( (*i)->GetFinishedLoadingTicks( ) - m_StartedLoadingTicks ) / 1000, 2 ) ) );


	m_GameLoadedTime = GetTime();
}

unsigned char CBaseGame :: GetSIDFromPID( unsigned char PID )
{
	if( m_Slots.size( ) > 255 )
		return 255;

        for( unsigned char i = 0; i < m_Slots.size( ); ++i )
	{
		if( m_Slots[i].GetPID( ) == PID )
			return i;
	}

	return 255;
}

CGamePlayer *CBaseGame :: GetPlayerFromPID( unsigned char PID )
{
        for( vector<CGamePlayer *> :: iterator i = m_Players.begin( ); i != m_Players.end( ); ++i )
	{
		if( !(*i)->GetLeftMessageSent( ) && (*i)->GetPID( ) == PID )
			return *i;
	}

	return NULL;
}

CGamePlayer *CBaseGame :: GetPlayerFromSID( unsigned char SID )
{
	if( SID < m_Slots.size( ) )
		return GetPlayerFromPID( m_Slots[SID].GetPID( ) );

	return NULL;
}

CGamePlayer *CBaseGame :: GetPlayerFromName( string name, bool sensitive )
{
	if( !sensitive )
		transform( name.begin( ), name.end( ), name.begin( ), (int(*)(int))tolower );

        for( vector<CGamePlayer *> :: iterator i = m_Players.begin( ); i != m_Players.end( ); ++i )
	{
		if( !(*i)->GetLeftMessageSent( ) )
		{
			string TestName = (*i)->GetName( );

			if( !sensitive )
				transform( TestName.begin( ), TestName.end( ), TestName.begin( ), (int(*)(int))tolower );

			if( TestName == name )
				return *i;
		}
	}

	return NULL;
}

uint32_t CBaseGame :: GetPlayerFromNamePartial( string name, CGamePlayer **player )
{
	transform( name.begin( ), name.end( ), name.begin( ), (int(*)(int))tolower );
	uint32_t Matches = 0;
	*player = NULL;

	// try to match each player with the passed string (e.g. "Varlock" would be matched with "lock")

        for( vector<CGamePlayer *> :: iterator i = m_Players.begin( ); i != m_Players.end( ); ++i )
	{
		if( !(*i)->GetLeftMessageSent( ) )
		{
			string TestName = (*i)->GetName( );
			transform( TestName.begin( ), TestName.end( ), TestName.begin( ), (int(*)(int))tolower );

			if( TestName.find( name ) != string :: npos )
			{
                                ++Matches;
				*player = *i;

				// if the name matches exactly stop any further matching

				if( TestName == name )
				{
					Matches = 1;
					break;
				}
			}
		}
	}

	return Matches;
}

CGamePlayer *CBaseGame :: GetPlayerFromColour( unsigned char colour )
{
        for( unsigned char i = 0; i < m_Slots.size( ); ++i )
	{
		if( m_Slots[i].GetColour( ) == colour )
			return GetPlayerFromSID( i );
	}

	return NULL;
}

string CBaseGame :: GetPlayerList( )
{
	string players = "";

	for( unsigned char i = 0; i < m_Slots.size( ); ++i )
	{
		if( m_Slots[i].GetSlotStatus( ) == SLOTSTATUS_OCCUPIED && m_Slots[i].GetComputer( ) == 0 )
		{
			CGamePlayer *player = GetPlayerFromSID( i );

			if( player )
				players += player->GetName( ) + "\t" + player->GetSpoofedRealm( ) + "\t" + UTIL_ToString( player->GetPing( m_GHost->m_LCPings ) ) + "\t";
		}

		else if( m_Slots[i].GetSlotStatus( ) == SLOTSTATUS_OPEN )
			players += "\t\t\t";
	}

	return players;
}

unsigned char CBaseGame :: GetNewPID( )
{
	// find an unused PID for a new player to use

        for( unsigned char TestPID = 1; TestPID < 255; ++TestPID )
	{
		if( TestPID == m_VirtualHostPID || TestPID == m_FakePlayerPID )
			continue;

		bool InUse = false;

                for( vector<CGamePlayer *> :: iterator i = m_Players.begin( ); i != m_Players.end( ); ++i )
		{
			if( !(*i)->GetLeftMessageSent( ) && (*i)->GetPID( ) == TestPID )
			{
				InUse = true;
				break;
			}
		}

		if( !InUse )
			return TestPID;
	}

	// this should never happen

	return 255;
}

unsigned char CBaseGame :: GetNewColour( )
{
	// find an unused colour for a player to use

        for( unsigned char TestColour = 0; TestColour < 12; ++TestColour )
	{
		bool InUse = false;

                for( unsigned char i = 0; i < m_Slots.size( ); ++i )
		{
			if( m_Slots[i].GetColour( ) == TestColour )
			{
				InUse = true;
				break;
			}
		}

		if( !InUse )
			return TestColour;
	}

	// this should never happen

	return 12;
}

BYTEARRAY CBaseGame :: Silence( BYTEARRAY PIDs)
{
	BYTEARRAY result;

	for(uint32_t j = 0; j<PIDs.size(); j++)
	{
		CGamePlayer *p = GetPlayerFromPID(PIDs[j]);
		if (p)
		if (!p->GetSilence())
			result.push_back(PIDs[j]);
		if (!p)
			result.push_back(PIDs[j]);
	}
	return result;
}

BYTEARRAY CBaseGame :: GetPIDs( )
{
	BYTEARRAY result;

        for( vector<CGamePlayer *> :: iterator i = m_Players.begin( ); i != m_Players.end( ); ++i )
	{
		if( !(*i)->GetLeftMessageSent( ) )
			result.push_back( (*i)->GetPID( ) );
	}

	return result;
}

BYTEARRAY CBaseGame :: GetPIDs( unsigned char excludePID )
{
	BYTEARRAY result;

        for( vector<CGamePlayer *> :: iterator i = m_Players.begin( ); i != m_Players.end( ); ++i )
	{
		if( !(*i)->GetLeftMessageSent( ) && (*i)->GetPID( ) != excludePID )
			result.push_back( (*i)->GetPID( ) );
	}

	return result;
}

unsigned char CBaseGame :: GetHostPID( )
{
	// return the player to be considered the host (it can be any player) - mainly used for sending text messages from the bot
	// try to find the virtual host player first

	if( m_VirtualHostPID != 255 )
		return m_VirtualHostPID;

	// try to find the fakeplayer next

	if( m_FakePlayerPID != 255 )
		return m_FakePlayerPID;

	// try to find the owner player next

        for( vector<CGamePlayer *> :: iterator i = m_Players.begin( ); i != m_Players.end( ); ++i )
	{
		if( !(*i)->GetLeftMessageSent( ) && IsOwner( (*i)->GetName( ) ) )
			return (*i)->GetPID( );
	}

	// okay then, just use the first available player

        for( vector<CGamePlayer *> :: iterator i = m_Players.begin( ); i != m_Players.end( ); ++i )
	{
		if( !(*i)->GetLeftMessageSent( ) )
			return (*i)->GetPID( );
	}

	return 255;
}

unsigned char CBaseGame :: GetEmptySlot( bool reserved )
{
	if( m_Slots.size( ) > 255 )
		return 255;

	if( m_SaveGame )
	{
		// unfortunately we don't know which slot each player was assigned in the savegame
		// but we do know which slots were occupied and which weren't so let's at least force players to use previously occupied slots

		vector<CGameSlot> SaveGameSlots = m_SaveGame->GetSlots( );

                for( unsigned char i = 0; i < m_Slots.size( ); ++i )
		{
			if( m_Slots[i].GetSlotStatus( ) == SLOTSTATUS_OPEN && SaveGameSlots[i].GetSlotStatus( ) == SLOTSTATUS_OCCUPIED && SaveGameSlots[i].GetComputer( ) == 0 )
				return i;
		}

		// don't bother with reserved slots in savegames
	}
	else
	{
		// look for an empty slot for a new player to occupy
		// if reserved is true then we're willing to use closed or occupied slots as long as it wouldn't displace a player with a reserved slot

                for( unsigned char i = 0; i < m_Slots.size( ); ++i )
		{
			if( m_Slots[i].GetSlotStatus( ) == SLOTSTATUS_OPEN )
				return i;
		}

		if( reserved )
		{
			// no empty slots, but since player is reserved give them a closed slot

			// dont allow to use closed slots to avoid players joining false slots when an observer fakeplayer is used
/*
                        for( unsigned char i = 0; i < m_Slots.size( ); ++i )
			{
				if( m_Slots[i].GetSlotStatus( ) == SLOTSTATUS_CLOSED )
					return i;
			}
*/
			// no closed slots either, give them an occupied slot but not one occupied by another reserved player
			// first look for a player who is downloading the map and has the least amount downloaded so far

			unsigned char LeastDownloaded = 100;
			unsigned char LeastSID = 255;

                        for( unsigned char i = 0; i < m_Slots.size( ); ++i )
			{
				CGamePlayer *Player = GetPlayerFromSID( i );

				if( Player && !Player->GetReserved( ) && m_Slots[i].GetDownloadStatus( ) < LeastDownloaded )
				{
					LeastDownloaded = m_Slots[i].GetDownloadStatus( );
					LeastSID = i;
				}
			}

			if( LeastSID != 255 )
				return LeastSID;

			// nobody who isn't reserved is downloading the map, just choose the first player who isn't reserved

                        for( unsigned char i = 0; i < m_Slots.size( ); ++i )
			{
				CGamePlayer *Player = GetPlayerFromSID( i );

				if( Player && !Player->GetReserved( ) )
					return i;
			}
		}
	}

	return 255;
}

unsigned char CBaseGame :: GetEmptySlot( unsigned char team, unsigned char PID )
{
	if( m_Slots.size( ) > 255 )
		return 255;

	// find an empty slot based on player's current slot

	unsigned char StartSlot = GetSIDFromPID( PID );

	if( StartSlot < m_Slots.size( ) )
	{
		if( m_Slots[StartSlot].GetTeam( ) != team )
		{
			// player is trying to move to another team so start looking from the first slot on that team
			// we actually just start looking from the very first slot since the next few loops will check the team for us

			StartSlot = 0;
		}

		if( m_SaveGame )
		{
			vector<CGameSlot> SaveGameSlots = m_SaveGame->GetSlots( );

                        for( unsigned char i = StartSlot; i < m_Slots.size( ); ++i )
			{
				if( m_Slots[i].GetSlotStatus( ) == SLOTSTATUS_OPEN && m_Slots[i].GetTeam( ) == team && SaveGameSlots[i].GetSlotStatus( ) == SLOTSTATUS_OCCUPIED && SaveGameSlots[i].GetComputer( ) == 0 )
					return i;
			}

                        for( unsigned char i = 0; i < StartSlot; ++i )
			{
				if( m_Slots[i].GetSlotStatus( ) == SLOTSTATUS_OPEN && m_Slots[i].GetTeam( ) == team && SaveGameSlots[i].GetSlotStatus( ) == SLOTSTATUS_OCCUPIED && SaveGameSlots[i].GetComputer( ) == 0 )
					return i;
			}
		}
		else
		{
			// find an empty slot on the correct team starting from StartSlot

                        for( unsigned char i = StartSlot; i < m_Slots.size( ); ++i )
			{
				if( m_Slots[i].GetSlotStatus( ) == SLOTSTATUS_OPEN && m_Slots[i].GetTeam( ) == team )
					return i;
			}

			// didn't find an empty slot, but we could have missed one with SID < StartSlot
			// e.g. in the DotA case where I am in slot 4 (yellow), slot 5 (orange) is occupied, and slot 1 (blue) is open and I am trying to move to another slot

                        for( unsigned char i = 0; i < StartSlot; ++i )
			{
				if( m_Slots[i].GetSlotStatus( ) == SLOTSTATUS_OPEN && m_Slots[i].GetTeam( ) == team )
					return i;
			}
		}
	}

	return 255;
}

void CBaseGame :: SwapSlots( unsigned char SID1, unsigned char SID2 )
{
	if( SID1 < m_Slots.size( ) && SID2 < m_Slots.size( ) && SID1 != SID2 )
	{
		CGameSlot Slot1 = m_Slots[SID1];
		CGameSlot Slot2 = m_Slots[SID2];

		if( m_Map->GetMapOptions( ) & MAPOPT_FIXEDPLAYERSETTINGS )
		{
			// don't swap the team, colour, race, or handicap
			m_Slots[SID1] = CGameSlot( Slot2.GetPID( ), Slot2.GetDownloadStatus( ), Slot2.GetSlotStatus( ), Slot2.GetComputer( ), Slot1.GetTeam( ), Slot1.GetColour( ), Slot1.GetRace( ), Slot2.GetComputerType( ), Slot1.GetHandicap( ) );
			m_Slots[SID2] = CGameSlot( Slot1.GetPID( ), Slot1.GetDownloadStatus( ), Slot1.GetSlotStatus( ), Slot1.GetComputer( ), Slot2.GetTeam( ), Slot2.GetColour( ), Slot2.GetRace( ), Slot1.GetComputerType( ), Slot2.GetHandicap( ) );
		}
		else
		{
			// swap everything

			if( m_Map->GetMapOptions( ) & MAPOPT_CUSTOMFORCES )
			{
				// except if custom forces is set, then we don't swap teams...
				Slot1.SetTeam( m_Slots[SID2].GetTeam( ) );
				Slot2.SetTeam( m_Slots[SID1].GetTeam( ) );
			}

			m_Slots[SID1] = Slot2;
			m_Slots[SID2] = Slot1;
		}

		SendAllSlotInfo( );
	}
}

void CBaseGame :: OpenSlot( unsigned char SID, bool kick )
{
	if( SID < m_Slots.size( ) )
	{
		if( kick )
		{
			CGamePlayer *Player = GetPlayerFromSID( SID );

			if( Player )
			{
				Player->SetDeleteMe( true );
				Player->SetLeftReason( "was kicked when opening a slot" );
				Player->SetLeftCode( PLAYERLEAVE_LOBBY );
			}
		}

		CGameSlot Slot = m_Slots[SID];
		m_Slots[SID] = CGameSlot( 0, 255, SLOTSTATUS_OPEN, 0, Slot.GetTeam( ), Slot.GetColour( ), Slot.GetRace( ) );
		SendAllSlotInfo( );
	}
}

void CBaseGame :: CloseSlot( unsigned char SID, bool kick )
{
	if( SID < m_Slots.size( ) )
	{
		if( kick )
		{
			CGamePlayer *Player = GetPlayerFromSID( SID );

			if( Player )
			{
				Player->SetDeleteMe( true );
				Player->SetLeftReason( "was kicked when closing a slot" );
				Player->SetLeftCode( PLAYERLEAVE_LOBBY );
			}
		}

		CGameSlot Slot = m_Slots[SID];
		m_Slots[SID] = CGameSlot( 0, 255, SLOTSTATUS_CLOSED, 0, Slot.GetTeam( ), Slot.GetColour( ), Slot.GetRace( ) ); 
		SendAllSlotInfo( );
	}
}

void CBaseGame :: ComputerSlot( unsigned char SID, unsigned char skill, bool kick )
{
	if( SID < m_Slots.size( ) && skill < 3 )
	{
		if( kick )
		{
			CGamePlayer *Player = GetPlayerFromSID( SID );

			if( Player )
			{
				Player->SetDeleteMe( true );
				Player->SetLeftReason( "was kicked when creating a computer in a slot" );
				Player->SetLeftCode( PLAYERLEAVE_LOBBY );
			}
		}

		CGameSlot Slot = m_Slots[SID];
		m_Slots[SID] = CGameSlot( 0, 100, SLOTSTATUS_OCCUPIED, 1, Slot.GetTeam( ), Slot.GetColour( ), Slot.GetRace( ), skill );
		SendAllSlotInfo( );
	}
}

void CBaseGame :: ColourSlot( unsigned char SID, unsigned char colour )
{
	if( SID < m_Slots.size( ) && colour < 12 )
	{
		// make sure the requested colour isn't already taken

		bool Taken = false;
		unsigned char TakenSID = 0;

                for( unsigned char i = 0; i < m_Slots.size( ); ++i )
		{
			if( m_Slots[i].GetColour( ) == colour )
			{
				TakenSID = i;
				Taken = true;
			}
		}

		if( Taken && m_Slots[TakenSID].GetSlotStatus( ) != SLOTSTATUS_OCCUPIED )
		{
			// the requested colour is currently "taken" by an unused (open or closed) slot
			// but we allow the colour to persist within a slot so if we only update the existing player's colour the unused slot will have the same colour
			// this isn't really a problem except that if someone then joins the game they'll receive the unused slot's colour resulting in a duplicate
			// one way to solve this (which we do here) is to swap the player's current colour into the unused slot

			m_Slots[TakenSID].SetColour( m_Slots[SID].GetColour( ) );
			m_Slots[SID].SetColour( colour );
			SendAllSlotInfo( );
		}
		else if( !Taken )
		{
			// the requested colour isn't used by ANY slot

			m_Slots[SID].SetColour( colour );
			SendAllSlotInfo( );
		}
	}
}

void CBaseGame :: OpenAllSlots( )
{
	bool Changed = false;

        for( vector<CGameSlot> :: iterator i = m_Slots.begin( ); i != m_Slots.end( ); ++i )
	{
		if( (*i).GetSlotStatus( ) == SLOTSTATUS_CLOSED )
		{
			(*i).SetSlotStatus( SLOTSTATUS_OPEN );
			Changed = true;
		}
	}

	if( Changed )
		SendAllSlotInfo( );
}

void CBaseGame :: CloseAllSlots( )
{
	bool Changed = false;

        for( vector<CGameSlot> :: iterator i = m_Slots.begin( ); i != m_Slots.end( ); ++i )
	{
		if( (*i).GetSlotStatus( ) == SLOTSTATUS_OPEN )
		{
			(*i).SetSlotStatus( SLOTSTATUS_CLOSED );
			Changed = true;
		}
	}

	if( Changed )
		SendAllSlotInfo( );
}

void CBaseGame :: ShuffleSlots( )
{
	// we only want to shuffle the player slots
	// that means we need to prevent this function from shuffling the open/closed/computer slots too
	// so we start by copying the player slots to a temporary vector

	vector<CGameSlot> PlayerSlots;

        for( vector<CGameSlot> :: iterator i = m_Slots.begin( ); i != m_Slots.end( ); ++i )
	{
                bool lockedplayer = false;
                for( vector<CGamePlayer *> :: iterator x = m_Players.begin( ); x != m_Players.end( ); ++x )
                {
                        if( GetSIDFromPID( (*x)->GetPID( ) ) == GetSIDFromPID( (*i).GetPID() ) )
                        {
                                if( (*x)->GetLocked( ) )
                                        lockedplayer = true;
                        }
                }
                if( !lockedplayer )
                {
			if( (*i).GetSlotStatus( ) == SLOTSTATUS_OCCUPIED && (*i).GetComputer( ) == 0 && (*i).GetTeam( ) != 12 )
				PlayerSlots.push_back( *i );
		}
	}

	// now we shuffle PlayerSlots

	if( m_Map->GetMapOptions( ) & MAPOPT_CUSTOMFORCES )
	{
		// rather than rolling our own probably broken shuffle algorithm we use random_shuffle because it's guaranteed to do it properly
		// so in order to let random_shuffle do all the work we need a vector to operate on
		// unfortunately we can't just use PlayerSlots because the team/colour/race shouldn't be modified
		// so make a vector we can use

		vector<unsigned char> SIDs;

                for( unsigned char i = 0; i < PlayerSlots.size( ); ++i )
			SIDs.push_back( i );

		random_shuffle( SIDs.begin( ), SIDs.end( ) );

		// now put the PlayerSlots vector in the same order as the SIDs vector

		vector<CGameSlot> Slots;

		// as usual don't modify the team/colour/race

                for( unsigned char i = 0; i < SIDs.size( ); ++i )
			Slots.push_back( CGameSlot( PlayerSlots[SIDs[i]].GetPID( ), PlayerSlots[SIDs[i]].GetDownloadStatus( ), PlayerSlots[SIDs[i]].GetSlotStatus( ), PlayerSlots[SIDs[i]].GetComputer( ), PlayerSlots[i].GetTeam( ), PlayerSlots[i].GetColour( ), PlayerSlots[i].GetRace( ) ) );

		PlayerSlots = Slots;
	}
	else
	{
		// regular game
		// it's easy when we're allowed to swap the team/colour/race!

		random_shuffle( PlayerSlots.begin( ), PlayerSlots.end( ) );
	}

	// now we put m_Slots back together again

	vector<CGameSlot> :: iterator CurrentPlayer = PlayerSlots.begin( );
	vector<CGameSlot> Slots;

        for( vector<CGameSlot> :: iterator i = m_Slots.begin( ); i != m_Slots.end( ); ++i )
	{
			if( (*i).GetSlotStatus( ) == SLOTSTATUS_OCCUPIED && (*i).GetComputer( ) == 0 && (*i).GetTeam( ) != 12 )
			{
				Slots.push_back( *CurrentPlayer );
                        	++CurrentPlayer;
			}
			else
				Slots.push_back( *i );
	}

	m_Slots = Slots;

	// and finally tell everyone about the new slot configuration

	SendAllSlotInfo( );
}

vector<unsigned char> CBaseGame :: BalanceSlotsRecursive( vector<unsigned char> PlayerIDs, unsigned char *TeamSizes, double *PlayerScores, unsigned char StartTeam )
{
	// take a brute force approach to finding the best balance by iterating through every possible combination of players
	// 1.) since the number of teams is arbitrary this algorithm must be recursive
	// 2.) on the first recursion step every possible combination of players into two "teams" is checked, where the first team is the correct size and the second team contains everyone else
	// 3.) on the next recursion step every possible combination of the remaining players into two more "teams" is checked, continuing until all the actual teams are accounted for
	// 4.) for every possible combination, check the largest difference in total scores between any two actual teams
	// 5.) minimize this value by choosing the combination of players with the smallest difference

	vector<unsigned char> BestOrdering = PlayerIDs;
	double BestDifference = -1.0;

        for( unsigned char i = StartTeam; i < 12; ++i )
	{
		if( TeamSizes[i] > 0 )
		{
			unsigned char Mid = TeamSizes[i];

			// the base case where only one actual team worth of players was passed to this function is handled by the behaviour of next_combination
			// in this case PlayerIDs.begin( ) + Mid will actually be equal to PlayerIDs.end( ) and next_combination will return false

			while( next_combination( PlayerIDs.begin( ), PlayerIDs.begin( ) + Mid, PlayerIDs.end( ) ) )
			{
				// we're splitting the players into every possible combination of two "teams" based on the midpoint Mid
				// the first (left) team contains the correct number of players but the second (right) "team" might or might not
				// for example, it could contain one, two, or more actual teams worth of players
				// so recurse using the second "team" as the full set of players to perform the balancing on

				vector<unsigned char> BestSubOrdering = BalanceSlotsRecursive( vector<unsigned char>( PlayerIDs.begin( ) + Mid, PlayerIDs.end( ) ), TeamSizes, PlayerScores, i + 1 );

				// BestSubOrdering now contains the best ordering of all the remaining players (the "right team") given this particular combination of players into two "teams"
				// in order to calculate the largest difference in total scores we need to recombine the subordering with the first team

				vector<unsigned char> TestOrdering = vector<unsigned char>( PlayerIDs.begin( ), PlayerIDs.begin( ) + Mid );
				TestOrdering.insert( TestOrdering.end( ), BestSubOrdering.begin( ), BestSubOrdering.end( ) );

				// now calculate the team scores for all the teams that we know about (e.g. on subsequent recursion steps this will NOT be every possible team)

				vector<unsigned char> :: iterator CurrentPID = TestOrdering.begin( );
				double TeamScores[12];

                                for( unsigned char j = StartTeam; j < 12; ++j )
				{
					TeamScores[j] = 0.0;

                                        for( unsigned char k = 0; k < TeamSizes[j]; ++k )
					{
						TeamScores[j] += PlayerScores[*CurrentPID];
                                                ++CurrentPID;
					}
				}

				// find the largest difference in total scores between any two teams

				double LargestDifference = 0.0;

                                for( unsigned char j = StartTeam; j < 12; ++j )
				{
					if( TeamSizes[j] > 0 )
					{
                                                for( unsigned char k = j + 1; k < 12; ++k )
						{
							if( TeamSizes[k] > 0 )
							{
								double Difference = abs( TeamScores[j] - TeamScores[k] );

								if( Difference > LargestDifference )
									LargestDifference = Difference;
							}
						}
					}
				}

				// and minimize it

				if( BestDifference < 0.0 || LargestDifference < BestDifference )
				{
					BestOrdering = TestOrdering;
					BestDifference = LargestDifference;
				}
			}
		}
	}

	return BestOrdering;
}

void CBaseGame :: OHFixedBalance( )
{
        //Main beginning
        if( !( m_Map->GetMapOptions( ) & MAPOPT_FIXEDPLAYERSETTINGS ) )
        {
                CONSOLE_Print( "[GAME: " + m_GameName + "] error balancing slots - can't balance slots without fixed player settings" );
                return;
        }

	// Getting values
        vector<double> PlayerWins;
	double totalwinpoints;
        for( vector<CGamePlayer *> :: iterator i = m_Players.begin( ); i != m_Players.end( ); ++i )
        {
	 if( m_Slots[GetSIDFromPID( (*i)->GetPID( ) )].GetTeam( ) != 12 && !(*i)->GetLocked( ) )
	 {
                int Win = 0;
       	        if( (*i)->GetGames( ) >= 10 )
               		Win = ((*i)->GetWinPerc( ) * (*i)->GetGames( ));
		else if( Win == 0 )
			Win = (33 * (*i)->GetGames( ));
		else if( (*i)->GetGames( ) == 0 )
			Win = 33;

                CONSOLE_Print( "Name: " + (*i)->GetName() + " | Win Points: " + UTIL_ToString( Win ) );
		totalwinpoints += Win;
       		PlayerWins.push_back( Win );
	 }
	}

	// find the best order
	uint32_t k = 0;
	for( vector<double> :: iterator i = PlayerWins.begin( ); i != PlayerWins.end( ); ++i )
		k =+ 1;

	if( k == 0 )
	{
		CONSOLE_Print( "Error, no players left" );
	}
	uint32_t counter = 0;
	double BestSpread = 100;
	vector<double> BestOrder;
        uint32_t StartTicks = GetTicks( );
	uint32_t z = 1;
	for( vector<double> :: iterator a = PlayerWins.begin( ); a != PlayerWins.end( ); ++a )
	{
		uint32_t y = 1;
		for( vector<double> :: iterator b = PlayerWins.begin( ); b != PlayerWins.end( ); ++b )
	        {
			uint32_t x = 1;
			if( z < y )
			{
				for( vector<double> :: iterator c = PlayerWins.begin( ); c != PlayerWins.end( ); ++c )
        	        	{
					uint32_t v = 1;
					if( y < x )
		                        {
						for( vector<double> :: iterator d = PlayerWins.begin( ); d != PlayerWins.end( ); ++d )
        	        			{
							uint32_t w = 1;
				                	if( x < v )
				                        {
								for( vector<double> :: iterator e = PlayerWins.begin( ); e != PlayerWins.end( ); ++e )
                						{
									if( v < w )
									{
										double teampoints = 0;
										double result;
										double CurrentSpread;
										teampoints = *a + *b + *c + *d + *e;
										result = ((teampoints*100)/totalwinpoints);
										if( result > 50 )
											CurrentSpread = result-50;
										else if( result < 50 )
											CurrentSpread = 50-result;
										else
											CurrentSpread = 0;

                                                                                if( CurrentSpread < BestSpread )
                                                                                {
											CONSOLE_Print("[OHBalance] Found a new best order: [Comb #" + UTIL_ToString( counter ) + "]" );
											CONSOLE_Print("[OHBalance] [P"+UTIL_ToString(z)+": "+UTIL_ToString(*a, 2)+"] [P"+UTIL_ToString(y)+": "+UTIL_ToString(*b, 2)+"] [P"+UTIL_ToString(x)+": "+UTIL_ToString(*c, 2)+"] [P"+UTIL_ToString(v)+": "+UTIL_ToString(*d, 2)+"] [P"+UTIL_ToString(w)+": "+UTIL_ToString(*e, 2)+"]" );
											CONSOLE_Print("[OHBalance] [WinPerc: "+UTIL_ToString( result, 2 ) + "] [New Best Spread: " + UTIL_ToString( CurrentSpread, 2 ) + "] [Old Best Spread: " +  UTIL_ToString( BestSpread, 2 ) + "]" );
											BestSpread = CurrentSpread;
											BestOrder.clear( );
											BestOrder.push_back( *a );
                                                                                        BestOrder.push_back( *b );
                                                                                        BestOrder.push_back( *c );
                                                                                        BestOrder.push_back( *d );
                                                                                        BestOrder.push_back( *e );
										}
										counter++;
									}
									w++;
								}
							}
							v++;
						}
					}
					x++;
				}
			}
			y++;
		}
		z++;
	}

        uint32_t EndTicks = GetTicks( );
	CONSOLE_Print( "Checked " + UTIL_ToString( counter ) + " possible combinations in " + UTIL_ToString( EndTicks-StartTicks ) + "ms." );

	if( !BestOrder.empty() )
	{
		sort( BestOrder.begin( ), BestOrder.end( ) );
		//now swap
        	for( vector<CGamePlayer *> :: iterator i = m_Players.begin( ); i != m_Players.end( ); ++i )
        	{
		 if( m_Slots[GetSIDFromPID( (*i)->GetPID( ) )].GetTeam( ) != 12 && !(*i)->GetLocked( ) )
		 {
			uint32_t g = 4;
			for( vector<double> :: iterator h = BestOrder.begin( ); h != BestOrder.end( ); ++h )
			{
				if( (*i)->GetGames( ) >= 10 )
				{
					if( ( (*i)->GetWinPerc( ) * (*i)->GetGames( ) ) == *h )
					{
                		                int oldpid = GetSIDFromPID( (*i)->GetPID( ) );

			                        if( (g) != oldpid )
        			                {
                			                CONSOLE_Print( "Swapping [" + UTIL_ToString( oldpid ) + "] to [" + UTIL_ToString( g ) + "]" );
                        			        SwapSlots( (unsigned char)oldpid, (unsigned char)g );
	                        		}
					}
				}
				else if( (*i)->GetGames( ) == 0 && *h == 33 )
				{
                                	int oldpid = GetSIDFromPID( (*i)->GetPID( ) );

                                        if( (g) != oldpid )
                                        {
                                        	CONSOLE_Print( "Swapping [" + UTIL_ToString( oldpid ) + "] to [" + UTIL_ToString( g ) + "]" );
                                                SwapSlots( (unsigned char)oldpid, (unsigned char)g );
                                        }
				}
				else
				{
					if( 33 * (*i)->GetGames( ) == *h )
                                	{
                                        	int oldpid = GetSIDFromPID( (*i)->GetPID( ) );

                                        	if( (g) != oldpid )
                                        	{
                                        	        CONSOLE_Print( "Swapping [" + UTIL_ToString( oldpid ) + "] to [" + UTIL_ToString( g ) + "]" );
                                	                SwapSlots( (unsigned char)oldpid, (unsigned char)g );
                        	                }
	                                }
				}
				g--;
			}
		 }
		}

		//for a better view order the other team also
		vector<double> OrderOtherTeam;
        	for( vector<CGamePlayer *> :: iterator i = m_Players.begin( ); i != m_Players.end( ); ++i )
        	{
		 if( m_Slots[GetSIDFromPID( (*i)->GetPID( ) )].GetTeam( ) != 12 && !(*i)->GetLocked( ) )
		 {
			int oldpid = GetSIDFromPID( (*i)->GetPID( ) );
			if( oldpid > 5 )
			{
	                	int Win = 0;
        	        	if( (*i)->GetGames( ) >= 10 )
                		        Win = ((*i)->GetWinPerc( ) * (*i)->GetGames( ));
                		else if( Win == 0 )
                	        	Win = (33 * (*i)->GetGames( ));
        		        else if( (*i)->GetGames( ) == 0 )
		                        Win = 33;

				OrderOtherTeam.push_back( Win );
			}
		 }
		}

		sort( OrderOtherTeam.begin(), OrderOtherTeam.end() );
		bool AllOrdered = false;
		counter = 0;
		if( !OrderOtherTeam.empty() )
		{
			for( vector<CGamePlayer *> :: iterator i = m_Players.begin( ); i != m_Players.end( ); ++i )
			{
			 if( m_Slots[GetSIDFromPID( (*i)->GetPID( ) )].GetTeam( ) != 12 && !(*i)->GetLocked( ) )
			 {
				uint32_t g = 9;
				for( vector<double> :: iterator h = OrderOtherTeam.begin( ); h != OrderOtherTeam.end( ); ++h )
	                	{
                                	if( (*i)->GetGames( ) >= 10 )
                        	        {
                	                        if( ( (*i)->GetWinPerc( ) * (*i)->GetGames( ) ) == *h )
        	                                {
	                                                int oldpid = GetSIDFromPID( (*i)->GetPID( ) );

        	                                        if( (g) != oldpid )
	                                                {
                                                        	CONSOLE_Print( "Swapping [" + UTIL_ToString( oldpid ) + "] to [" + UTIL_ToString( g ) + "]" );
                                                        	SwapSlots( (unsigned char)oldpid, (unsigned char)g );
                                                	}
						}
                        		}
                		        else if( (*i)->GetGames( ) == 0 && *h == 33 )
        		                {
		                        	int oldpid = GetSIDFromPID( (*i)->GetPID( ) );

                        	                if( (g) != oldpid )
                	                	{
        	                	        	CONSOLE_Print( "Swapping [" + UTIL_ToString( oldpid ) + "] to [" + UTIL_ToString( g ) + "]" );
	                	                        SwapSlots( (unsigned char)oldpid, (unsigned char)g );
                	        	        }
	                               	}
        	                        else
                	        	{
                	                        if( 33 * (*i)->GetGames( ) == *h )
        	        	                {
	                                		int oldpid = GetSIDFromPID( (*i)->GetPID( ) );
                                                	if( (g) != oldpid )
       	                                        	{
               	                        	                CONSOLE_Print( "Swapping [" + UTIL_ToString( oldpid ) + "] to [" + UTIL_ToString( g ) + "]" );
                       	        	                        SwapSlots( (unsigned char)oldpid, (unsigned char)g );
                       		                        }
	                                        }

					}
					g--;
				}
			 }
			}
		}

		//and finish the process with an output
                m_ScourgeWinPoints = 0.0;
                m_SentinelWinPoints = 0.0;
                m_TotalWinPoints = 0.0;
		string SeWP;
		string ScWP;
		m_LockedPlayers = 0;
                for( vector<CGamePlayer *> :: iterator i = m_Players.begin( ); i != m_Players.end( ); ++i )
                {
		 if( m_Slots[GetSIDFromPID( (*i)->GetPID( ) )].GetTeam( ) != 12 )
		 {
			if( (*i)->GetLocked( ) )
				++m_LockedPlayers;

                        if( GetSIDFromPID( (*i)->GetPID( ) ) < 5 )
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
                        if( GetSIDFromPID( (*i)->GetPID( ) ) >= 5 )
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
		 }
                }
                if( m_TotalWinPoints != 0 )
                {
                        SeWP = UTIL_ToString( ( m_SentinelWinPoints / m_TotalWinPoints ) * 100, 1);
                        ScWP = UTIL_ToString( ( m_ScourgeWinPoints / m_TotalWinPoints ) * 100, 1);
                }

		SendAllChat( "[Sentinel rating: " + UTIL_ToString( m_SentinelWinPoints, 2 ) + "] [Scourge rating: " + UTIL_ToString( m_ScourgeWinPoints, 2 ) + "]" );
        	SendAllChat( "[Total Spread: " + UTIL_ToString( (m_SentinelWinPoints-m_ScourgeWinPoints), 2 ) + "] with ["+UTIL_ToString(m_LockedPlayers)+"] locked players" );
	        SendAllChat( "[Team WinChance: (Sentinel:" + SeWP + "%) (Scourge:" + ScWP + "%)]" );

	}
	else
		SendAllChat( "Error, to less slots token" );
}


void CBaseGame :: BalanceSlots( )
{
	if( !( m_Map->GetMapOptions( ) & MAPOPT_FIXEDPLAYERSETTINGS ) )
	{
		CONSOLE_Print( "[GAME: " + m_GameName + "] error balancing slots - can't balance slots without fixed player settings" );
		return;
	}

	// setup the necessary variables for the balancing algorithm
	// use an array of 13 elements for 12 players because GHost++ allocates PID's from 1-12 (i.e. excluding 0) and we use the PID to index the array

	vector<unsigned char> PlayerIDs;
	unsigned char TeamSizes[12];
	double PlayerScores[13];
	memset( TeamSizes, 0, sizeof( unsigned char ) * 12 );

        for( vector<CGamePlayer *> :: iterator i = m_Players.begin( ); i != m_Players.end( ); ++i )
	{
		unsigned char PID = (*i)->GetPID( );

		if( PID < 13 )
		{
			unsigned char SID = GetSIDFromPID( PID );

			if( SID < m_Slots.size( ) )
			{
				unsigned char Team = m_Slots[SID].GetTeam( );

				if( Team < 12 )
				{
					double Score = 0.0;
					// we are forced to use a default score because there's no way to balance the teams otherwise
					if( (*i)->GetGames() > 10 )
						double Score = (*i)->GetWinPerc( );
					//double Score = (*i)->GetScore( );

					if( Score < -99999.0 )
						Score = m_Map->GetMapDefaultPlayerScore( );

					PlayerIDs.push_back( PID );
					TeamSizes[Team]++;
					PlayerScores[PID] = Score;
				}
			}
		}
	}

	sort( PlayerIDs.begin( ), PlayerIDs.end( ) );

	// balancing the teams is a variation of the bin packing problem which is NP
	// we can have up to 12 players and/or teams so the scope of the problem is sometimes small enough to process quickly
	// let's try to figure out roughly how much work this is going to take
	// examples:
	//  2 teams of 4 =     70 ~    5ms *** ok
	//  2 teams of 5 =    252 ~    5ms *** ok
	//  2 teams of 6 =    924 ~   20ms *** ok
	//  3 teams of 2 =     90 ~    5ms *** ok
	//  3 teams of 3 =   1680 ~   25ms *** ok
	//  3 teams of 4 =  34650 ~  250ms *** will cause a lag spike
	//  4 teams of 2 =   2520 ~   30ms *** ok
	//  4 teams of 3 = 369600 ~ 3500ms *** unacceptable

	uint32_t AlgorithmCost = 0;
	uint32_t PlayersLeft = PlayerIDs.size( );

        for( unsigned char i = 0; i < 12; ++i )
	{
		if( TeamSizes[i] > 0 )
		{
			if( AlgorithmCost == 0 )
				AlgorithmCost = nCr( PlayersLeft, TeamSizes[i] );
			else
				AlgorithmCost *= nCr( PlayersLeft, TeamSizes[i] );

			PlayersLeft -= TeamSizes[i];
		}
	}

	if( AlgorithmCost > 40000 )
	{
		// the cost is too high, don't run the algorithm
		// a possible alternative: stop after enough iterations and/or time has passed

		CONSOLE_Print( "[GAME: " + m_GameName + "] shuffling slots instead of balancing - the algorithm is too slow (with a cost of " + UTIL_ToString( AlgorithmCost ) + ") for this team configuration" );
		SendAllChat( m_GHost->m_Language->ShufflingPlayers( ) );
		ShuffleSlots( );
		return;
	}

	uint32_t StartTicks = GetTicks( );
	vector<unsigned char> BestOrdering = BalanceSlotsRecursive( PlayerIDs, TeamSizes, PlayerScores, 0 );
	uint32_t EndTicks = GetTicks( );

	// the BestOrdering assumes the teams are in slot order although this may not be the case
	// so put the players on the correct teams regardless of slot order

	vector<unsigned char> :: iterator CurrentPID = BestOrdering.begin( );

        for( unsigned char i = 0; i < 12; ++i )
	{
		unsigned char CurrentSlot = 0;

                for( unsigned char j = 0; j < TeamSizes[i]; ++j )
		{
			while( CurrentSlot < m_Slots.size( ) && m_Slots[CurrentSlot].GetTeam( ) != i )
                                ++CurrentSlot;

			// put the CurrentPID player on team i by swapping them into CurrentSlot

			unsigned char SID1 = CurrentSlot;
			unsigned char SID2 = GetSIDFromPID( *CurrentPID );

			bool lockedplayer = false;
       		        for( vector<CGamePlayer *> :: iterator x = m_Players.begin( ); x != m_Players.end( ); ++x )
	                {
				if( GetSIDFromPID( (*x)->GetPID( ) ) == GetSIDFromPID( *CurrentPID ) )
				{
					if( (*x)->GetLocked( ) )
						lockedplayer = true;
				}
			}

			if( SID1 < m_Slots.size( ) && SID2 < m_Slots.size( ) )
			{
				if( !lockedplayer )
				{
					CGameSlot Slot1 = m_Slots[SID1];
					CGameSlot Slot2 = m_Slots[SID2];
					m_Slots[SID1] = CGameSlot( Slot2.GetPID( ), Slot2.GetDownloadStatus( ), Slot2.GetSlotStatus( ), Slot2.GetComputer( ), Slot1.GetTeam( ), Slot1.GetColour( ), Slot1.GetRace( ) );
					m_Slots[SID2] = CGameSlot( Slot1.GetPID( ), Slot1.GetDownloadStatus( ), Slot1.GetSlotStatus( ), Slot1.GetComputer( ), Slot2.GetTeam( ), Slot2.GetColour( ), Slot2.GetRace( ) );
				}
			}
			else
			{
				CONSOLE_Print( "[GAME: " + m_GameName + "] shuffling slots instead of balancing - the balancing algorithm tried to do an invalid swap (this shouldn't happen)" );
				SendAllChat( m_GHost->m_Language->ShufflingPlayers( ) );
				ShuffleSlots( );
				return;
			}

                        ++CurrentPID;
                        ++CurrentSlot;
		}
	}

	CONSOLE_Print( "[GAME: " + m_GameName + "] balancing slots completed in " + UTIL_ToString( EndTicks - StartTicks ) + "ms (with a cost of " + UTIL_ToString( AlgorithmCost ) + ")" );
	SendAllChat( m_GHost->m_Language->BalancingSlotsCompleted( ) );
	SendAllSlotInfo( );

        for( unsigned char i = 0; i < 12; ++i )
	{
		bool TeamHasPlayers = false;
		double TeamScore = 0.0;

                for( vector<CGamePlayer *> :: iterator j = m_Players.begin( ); j != m_Players.end( ); ++j )
		{
			unsigned char SID = GetSIDFromPID( (*j)->GetPID( ) );

			if( SID < m_Slots.size( ) && m_Slots[SID].GetTeam( ) == i )
			{
				TeamHasPlayers = true;
				double Score = (*j)->GetWinPerc();
				//double Score = (*j)->GetScore( );

				if( Score < -99999.0 )
					Score = m_Map->GetMapDefaultPlayerScore( );

				TeamScore += Score;
			}
		}

		if( TeamHasPlayers )
			SendAllChat( m_GHost->m_Language->TeamCombinedScore( UTIL_ToString( i + 1 ), UTIL_ToString( TeamScore, 2 ) ) );
	}
}

void CBaseGame :: AddToSpoofed( string server, string name, bool sendMessage )
{
	CGamePlayer *Player = GetPlayerFromName( name, true );

	if( Player )
	{
		Player->SetSpoofedRealm( server );
		Player->SetSpoofed( true );

		if( sendMessage )
			SendAllChat( m_GHost->m_Language->SpoofCheckAcceptedFor( server, name ) );
	}
}

void CBaseGame :: AddToReserved( string name )
{
	transform( name.begin( ), name.end( ), name.begin( ), (int(*)(int))tolower );

	// check that the user is not already reserved

        for( vector<string> :: iterator i = m_Reserved.begin( ); i != m_Reserved.end( ); ++i )
	{
		if( *i == name )
			return;
	}

	m_Reserved.push_back( name );

	// upgrade the user if they're already in the game

        for( vector<CGamePlayer *> :: iterator i = m_Players.begin( ); i != m_Players.end( ); ++i )
	{
		string NameLower = (*i)->GetName( );
		transform( NameLower.begin( ), NameLower.end( ), NameLower.begin( ), (int(*)(int))tolower );

		if( NameLower == name )
			(*i)->SetReserved( true );
	}
}

bool CBaseGame :: IsOwner( string name )
{
	string OwnerLower = m_OwnerName;
	transform( name.begin( ), name.end( ), name.begin( ), (int(*)(int))tolower );
	transform( OwnerLower.begin( ), OwnerLower.end( ), OwnerLower.begin( ), (int(*)(int))tolower );
	return name == OwnerLower;
}

bool CBaseGame :: IsReserved( string name )
{
	transform( name.begin( ), name.end( ), name.begin( ), (int(*)(int))tolower );

        for( vector<string> :: iterator i = m_Reserved.begin( ); i != m_Reserved.end( ); ++i )
	{
		if( *i == name )
			return true;
	}

	return false;
}

bool CBaseGame :: IsDownloading( )
{
	// returns true if at least one player is downloading the map

        for( vector<CGamePlayer *> :: iterator i = m_Players.begin( ); i != m_Players.end( ); ++i )
	{
		if( (*i)->GetDownloadStarted( ) && !(*i)->GetDownloadFinished( ) )
			return true;
	}

	return false;
}

bool CBaseGame :: IsGameDataSaved( )
{
	return true;
}

void CBaseGame :: SaveGameData( )
{

}

void CBaseGame :: StartCountDown( bool force )
{
	if( !m_CountDownStarted )
	{
		if( force )
		{
			m_CountDownStarted = true;
			m_CountDownCounter = 10;
		}
		else
		{
			// check if the HCL command string is short enough

			if( m_HCLCommandString.size( ) > GetSlotsOccupied( ) )
			{
				SendAllChat( m_GHost->m_Language->TheHCLIsTooLongUseForceToStart( ) );
				return;
			}

			// check if everyone has the map

			string StillDownloading;

                        for( vector<CGameSlot> :: iterator i = m_Slots.begin( ); i != m_Slots.end( ); ++i )
			{
				if( (*i).GetSlotStatus( ) == SLOTSTATUS_OCCUPIED && (*i).GetComputer( ) == 0 && (*i).GetDownloadStatus( ) != 100 )
				{
					CGamePlayer *Player = GetPlayerFromPID( (*i).GetPID( ) );

					if( Player )
					{
						if( StillDownloading.empty( ) )
							StillDownloading = Player->GetName( );
						else
							StillDownloading += ", " + Player->GetName( );
					}
				}
			}

			if( !StillDownloading.empty( ) )
			{
				SendAllChat( m_GHost->m_Language->PlayersStillDownloading( StillDownloading ) );
				return;
			}

			// check if everyone is spoof checked

			// check if everyone has been pinged enough (3 times) that the autokicker would have kicked them by now
			// see function EventPlayerPongToHost for the autokicker code
                        // check if all password protected users typed the password

			string NotPinged;
                        string NotSpoofChecked;
                        string NotPassword;

                        for( vector<CGamePlayer *> :: iterator i = m_Players.begin( ); i != m_Players.end( ); ++i )
			{
				if( !(*i)->GetReserved( ) && (*i)->GetNumPings( ) < 3 )
				{
					if( NotPinged.empty( ) )
						NotPinged = (*i)->GetName( );
					else
						NotPinged += ", " + (*i)->GetName( );
				}

				if( m_GHost->m_RequireSpoofChecks )
				{
                                        if( !(*i)->GetSpoofed( ) )
                                        {
                                                if( NotSpoofChecked.empty( ) )
                                                        NotSpoofChecked = (*i)->GetName( );
                                                else
                                                        NotSpoofChecked += ", " + (*i)->GetName( );
                                        }
				}

                                if( (*i)->GetPasswordProt( ) )
                                {
                                        if( NotPassword.empty( ) )
                                                NotPassword = (*i)->GetName( );
                                        else
                                               NotPassword += ", " + (*i)->GetName( );
                                }

			}

			if( !NotPinged.empty( ) )
			{
				SendAllChat( m_GHost->m_Language->PlayersNotYetPinged( NotPinged ) );
				return;
			}

                        if( !NotSpoofChecked.empty( ) )
			{
                                SendAllChat( m_GHost->m_Language->PlayersNotYetSpoofChecked( NotSpoofChecked ) );
				return;
			}

                        if( !NotPassword.empty( ) )
			{
                                SendAllChat( "Players who are not yet verified: " + NotPassword );
				return;
			}

			if( !m_Balanced && m_GHost->m_OHBalance )
			{
				OHFixedBalance( );
				m_Balanced = true;
			}

			// if no problems found start the game

			if( StillDownloading.empty( ) && NotSpoofChecked.empty( ) && NotPinged.empty( ) && NotPassword.empty( ) && ( m_Balanced || ( !m_Balanced && !m_GHost->m_OHBalance ) ) )
			{
				m_CountDownStarted = true;
				m_CountDownCounter = 10;
			}
		}
	}
}

void CBaseGame :: StartCountDownAuto( bool requireSpoofChecks )
{
	if( !m_CountDownStarted )
	{
		// check if enough players are present

		if( GetNumHumanPlayers( ) < m_AutoStartPlayers )
		{
			SendAllChat( m_GHost->m_Language->WaitingForPlayersBeforeAutoStart( UTIL_ToString( m_AutoStartPlayers ), UTIL_ToString( m_AutoStartPlayers - GetNumHumanPlayers( ) ) ) );
			return;
		}

		// check if everyone has the map

		string StillDownloading;

                for( vector<CGameSlot> :: iterator i = m_Slots.begin( ); i != m_Slots.end( ); ++i )
		{
			if( (*i).GetSlotStatus( ) == SLOTSTATUS_OCCUPIED && (*i).GetComputer( ) == 0 && (*i).GetDownloadStatus( ) != 100 )
			{
				CGamePlayer *Player = GetPlayerFromPID( (*i).GetPID( ) );

				if( Player )
				{
					if( StillDownloading.empty( ) )
						StillDownloading = Player->GetName( );
					else
						StillDownloading += ", " + Player->GetName( );
				}
			}
		}

		if( !StillDownloading.empty( ) )
		{
			SendAllChat( m_GHost->m_Language->PlayersStillDownloading( StillDownloading ) );
			return;
		}

                // check if everyone has been pinged enough (3 times) that the autokicker would have kicked them by now
                // see function EventPlayerPongToHost for the autokicker code
                // check if all password protected users typed the password

                string NotPinged;
                string NotSpoofChecked;
                string NotPassword;

                for( vector<CGamePlayer *> :: iterator i = m_Players.begin( ); i != m_Players.end( ); ++i )
                {
                        if( !(*i)->GetReserved( ) && (*i)->GetNumPings( ) < 3 )
                        {
                                if( NotPinged.empty( ) )
                                        NotPinged = (*i)->GetName( );
                                else
                                        NotPinged += ", " + (*i)->GetName( );
                        }
                        if( m_GHost->m_RequireSpoofChecks )
                        {
                                if( !(*i)->GetSpoofed( ) )
                                {
                                        if( NotSpoofChecked.empty( ) )
                                                NotSpoofChecked = (*i)->GetName( );
                                        else
                                                NotSpoofChecked += ", " + (*i)->GetName( );
                                }
                        }

                        if( (*i)->GetPasswordProt( ) )
                        {
                                 if( NotPassword.empty( ) )
                                        NotPassword = (*i)->GetName( );
                                 else
                                        NotPassword += ", " + (*i)->GetName( );
                        }
                }

                if( !NotPinged.empty( ) )
                {
                        SendAllChat( m_GHost->m_Language->PlayersNotYetPinged( NotPinged ) );
                        return;
                }

                if( !NotSpoofChecked.empty( ) )
                {
                        SendAllChat( m_GHost->m_Language->PlayersNotYetSpoofChecked( NotSpoofChecked ) );
                        return;
                }

                if( !NotPassword.empty( ) )
                {
                        SendAllChat( "Players who are not yet verified: " + NotPassword );
                        return;
                }


                if( !m_Balanced && m_GHost->m_OHBalance )
                {
                        OHFixedBalance( );
	                m_Balanced = true;
                }

		// if no problems found start the game

		if( StillDownloading.empty( ) && NotSpoofChecked.empty( ) && NotPinged.empty( ) && NotPassword.empty( ) && ( m_Balanced || ( !m_Balanced && !m_GHost->m_OHBalance ) ) )
		{
			m_CountDownStarted = true;
			m_CountDownCounter = 10;
		}
	}
}

void CBaseGame :: StopPlayers( string reason )
{
	// disconnect every player and set their left reason to the passed string
	// we use this function when we want the code in the Update function to run before the destructor (e.g. saving players to the database)
	// therefore calling this function when m_GameLoading || m_GameLoaded is roughly equivalent to setting m_Exiting = true
	// the only difference is whether the code in the Update function is executed or not

        for( vector<CGamePlayer *> :: iterator i = m_Players.begin( ); i != m_Players.end( ); ++i )
	{
		(*i)->SetDeleteMe( true );
		(*i)->SetLeftReason( reason );
		(*i)->SetLeftCode( PLAYERLEAVE_LOST );
	}
}

void CBaseGame :: StopLaggers( string reason )
{
        for( vector<CGamePlayer *> :: iterator i = m_Players.begin( ); i != m_Players.end( ); ++i )
	{
		if( (*i)->GetLagging( ) )
		{
			(*i)->SetDeleteMe( true );
			(*i)->SetLeftReason( reason );
			(*i)->SetLeftCode( PLAYERLEAVE_DISCONNECT );
        	        if( (*i)->GetLeavePerc() >= 20 && !(*i)->GetGProxy() && !(*i)->GetSafeDrop( ) )
				(*i)->SetAutoban( true );
			(*i)->SetLeft( true );
		}
	}
}

void CBaseGame :: CreateVirtualHost( )
{
	if( m_VirtualHostPID != 255 )
		return;

	m_VirtualHostPID = GetNewPID( );
	BYTEARRAY IP;
	IP.push_back( 0 );
	IP.push_back( 0 );
	IP.push_back( 0 );
	IP.push_back( 0 );
	SendAll( m_Protocol->SEND_W3GS_PLAYERINFO( m_VirtualHostPID, m_VirtualHostName, IP, IP, string( ) ) );
}

void CBaseGame :: DeleteVirtualHost( )
{
	if( m_VirtualHostPID == 255 )
		return;

	SendAll( m_Protocol->SEND_W3GS_PLAYERLEAVE_OTHERS( m_VirtualHostPID, PLAYERLEAVE_LOBBY ) );
	m_VirtualHostPID = 255;
}

void CBaseGame :: CreateFakePlayer( )
{
	if( m_FakePlayerPID != 255 )
		return;

	unsigned char SID = GetEmptySlot( false );

	if( SID < m_Slots.size( ) )
	{
		if( GetSlotsAllocated( ) >= m_Slots.size() - 1 )
			DeleteVirtualHost( );

		m_FakePlayerPID = GetNewPID( );
		BYTEARRAY IP;
		IP.push_back( 0 );
		IP.push_back( 0 );
		IP.push_back( 0 );
		IP.push_back( 0 );
		if( m_GHost->m_ObserverFake ) {
			SendAll( m_Protocol->SEND_W3GS_PLAYERINFO( m_FakePlayerPID, "|cFFFF0000Ghost", IP, IP, string( ) ) );
			m_Slots[SID] = CGameSlot( m_FakePlayerPID, 100, SLOTSTATUS_OCCUPIED, 0, m_Slots[SID].GetTeam( ), m_Slots[SID].GetColour( ), m_Slots[SID].GetRace( ) );
		} else {
                        SendAll( m_Protocol->SEND_W3GS_PLAYERINFO( m_FakePlayerPID, "|cFFFF0000Ghost", IP, IP, string( ) ) );
                        m_Slots[SID] = CGameSlot( m_FakePlayerPID, 100, SLOTSTATUS_OCCUPIED, 0, m_Slots[SID].GetTeam( ), m_Slots[SID].GetColour( ), m_Slots[SID].GetRace( ) );
		}

		SendAllSlotInfo( );
		if( m_GHost->m_ObserverFake )
			SwapSlots( 0, 10 );
	}
}

void CBaseGame :: DeleteFakePlayer( )
{
	if( m_FakePlayerPID == 255 )
		return;

        for( unsigned char i = 0; i < m_Slots.size( ); ++i )
	{
		if( m_Slots[i].GetPID( ) == m_FakePlayerPID )
			m_Slots[i] = CGameSlot( 0, 255, SLOTSTATUS_OPEN, 0, m_Slots[i].GetTeam( ), m_Slots[i].GetColour( ), m_Slots[i].GetRace( ) );
	}

	SendAll( m_Protocol->SEND_W3GS_PLAYERLEAVE_OTHERS( m_FakePlayerPID, PLAYERLEAVE_LOBBY ) );
	SendAllSlotInfo( );
	m_FakePlayerPID = 255;
}

bool CBaseGame :: IsDenied( string username, string ip )
{
        for( vector<string> :: iterator i = m_Denied.begin( ); i != m_Denied.end( );)
        {
                string deniedusername;
                string deniedip;
                string denietime;
                stringstream SS;
                SS << *i;
                SS >> deniedusername;
                SS >> deniedip;
                SS >> denietime;
                if( UTIL_ToUInt32(denietime) == 0 )
                {
                        if( username == deniedusername )
                                return true;
                        if( ip == deniedip )
                                return true;
                        i++;
                }
                else if(  GetTime( ) - UTIL_ToUInt32( denietime ) <= 60 )
                {
                        if( username == deniedusername )
                                return true;
                        if( ip == deniedip )
                                return true;
                        i++;
                }
                else
                        i=m_Denied.erase( i );
        }

        return false;
}

bool CBaseGame :: is_digits( const std::string &str )
{
	return str.find_first_not_of("0123456789") == std::string::npos;
}

void CBaseGame :: GAME_Print( uint32_t type, string MinString, string SecString, string Player1, string Player2, string message )
{
	string StorePacket;
	if( type == 0 || type == 3 || type == 4 || type == 9 )
	{
		if( type == 0 )
			StorePacket = "<div class=\"lobbychat\"><span class=\"bot\">Bot</span> "+message+"</div>";
		if( type == 3 )
			StorePacket = "<div class=\"lobbyleave\"><span class=\"lobbyplayer\">"+Player1+"</span> "+message+"</div>";
                if( type == 4 )
                        StorePacket = "<div class=\"lobbyjoin\"><span class=\"lobbyplayer\">"+Player1+"</span> joined the game from "+message+"</div>";
		if( type == 9 )
			StorePacket = "<div class=\"lobbychat\"><span class=\"player\">"+Player1+" </span>"+message+"</div>";

		m_LobbyLog.push_back( StorePacket );
	}
	else if( type <= 11 )
	{
		string PSiD = "0";
                CGamePlayer *Player = GetPlayerFromName( Player1, true );
                if( Player )
              		PSiD = UTIL_ToString( GetSIDFromPID( Player->GetPID( ) ) );

		if( type == 1 )
			 StorePacket = "<div class=\"gamechat\"><span class=\"bot\">Bot</span> "+message+"</div>";
		else if( type == 2 )
			StorePacket = "<div class=\"gameleave\"><a href=\"?u="+Player1+"\"><span class=\"slot"+PSiD+"\">"+Player1+"</span></a> "+message+"</div>";
		else if( type == 5 )
			StorePacket = "<div class=\"sentinelchat\"><a href=\"?u="+Player1+"\"><span class=\"slot"+PSiD+"\">"+Player1+"</span></a> "+message+"</div>";
                else if( type == 6 )
                        StorePacket = "<div class=\"scourgechat\"><a href=\"?u="+Player1+"\"><span class=\"slot"+PSiD+"\">"+Player1+"</span></a> "+message+"</div>";
                else if( type == 7 )
                        StorePacket = "<div class=\"gamechat\"><a href=\"?u="+Player1+"\"><span class=\"slot"+PSiD+"\">"+Player1+"</span></a> "+message+"</div>";
                else if( type == 8 )
                        StorePacket = "<div class=\"obschat\"><a href=\"?u="+Player1+"\"><span class=\"slot"+PSiD+"\">"+Player1+"</span></a> "+message+"</div>";
                else if( type == 10 )
                        StorePacket = "<div class=\"systemchat\"><span class=\"system\">System</span> "+message+"</div>";
                else if( type == 11 )
                        StorePacket = "<div class=\"lagevent\"><a href=\"?u="+Player1+"\"><span class=\"slot"+PSiD+"\">"+Player1+"</span></a> "+message+"ed lagging</div>";
		else
			CONSOLE_Print( "Invalid gameprint packet send: "+UTIL_ToString(type)+": "+message );

		m_GameLog.push_back( StorePacket );
	}
	else if( type > 11 && type <= 31 )
	{
                string PSiD = "0";
		string VSiD = "0";
                CGamePlayer *Player = GetPlayerFromName( Player1, true );
		CGamePlayer *VPlayer = GetPlayerFromName( Player2, true );
                if( Player )
                        PSiD = UTIL_ToString( GetSIDFromPID( Player->GetPID( ) ) );
                if( VPlayer )
                        PSiD = UTIL_ToString( GetSIDFromPID( VPlayer->GetPID( ) ) );

		if( type == 12 )
			StorePacket = "<div class=\"system\"><a href=\"?u="+Player1+"\"><span class=\"slot"+PSiD+"\">"+Player1+"</span></a> denied <a href=\"?u="+Player2+"\"><span class=\"slot"+VSiD+"\">"+Player2+"</span></a></div>";
		else if( type == 13 )
			StorePacket = "<div class=\"system\"><a href=\"?u="+Player1+"\"><span class=\"slot"+PSiD+"\">"+Player1+"</span></a> drew a first blood on <a href=\"?u="+Player2+"\"><span class=\"slot"+VSiD+"\">"+Player2+"</span></a></div>";
		else if( type == 14 )
			StorePacket = "<div class=\"system\"><a href=\"?u="+Player1+"\"><span class=\"slot"+PSiD+"\">"+Player1+"</span></a> killed <a href=\"?u="+Player2+"\"><span class=\"slot"+VSiD+"\">"+Player2+"</span></a></div>";
		else if( type == 15 )
			StorePacket = "<div class=\"system\"><a href=\"?u="+Player1+"\"><span class=\"slot"+PSiD+"\">"+Player1+"</span></a> was killed by the creeps or suicided.</div>";
		else if( type == 16 )
			StorePacket = "<div class=\"system\"><a href=\"?u="+Player1+"\"><span class=\"slot"+PSiD+"\">"+Player1+"</span></a> killed by the <span class=\"sentinel\">Sentinel</span></div>";
                else if( type == 17 )
                        StorePacket = "<div class=\"system\"><a href=\"?u="+Player1+"\"><span class=\"slot"+PSiD+"\">"+Player1+"</span></a> killed by the <span class=\"scourge\">Scourge</span></div>";
		else if( type == 18 )
			StorePacket = "<div class=\"system\"><a href=\"?u="+Player1+"\"><span class=\"slot"+PSiD+"\">"+Player1+"</span></a> assisted to kill <a href=\"?u="+Player2+"\"><span class=\"slot"+VSiD+"\">"+Player2+"</span></a></div>";
		else if( type == 19 )
		{
			string Type;
			string Level;
			stringstream SS;
			SS >> message;
			SS << Type;
			SS << Level;
			StorePacket = "<div class=\"system\"><a href=\"?u="+Player1+"\"><span class=\"slot"+PSiD+"\">"+Player1+"</span></a> killed a "+Type+" <span class=\""+Player2+"\">"+Player2+"</span> level "+Level+" Tower.</div>";
		}
		else if( type == 20 )
		{
                        string Type;
                        string Level;
                        stringstream SS;
                        SS >> message;
                        SS << Type;
                        SS << Level;
			StorePacket = "<div class=\"system\"><span class=\"sentinel\">Sentinel</span> killed a "+Type+" <span class=\"scourge\">Scourge</span> level "+Level+" Tower.</div>";
		}
                else if( type == 21 )
                {
                        string Type;
                        string Level;
                        stringstream SS;
                        SS >> message;
                        SS << Type;
                        SS << Level;
                        StorePacket = "<div class=\"system\"><span class=\"scourge\">Scourge</span> killed a "+Type+" <span class=\"sentinel\">Sentinel</span> level "+Level+" Tower.</div>";
                }
		else if( type == 22 )
		{
                        string Type;
                        string Level;
                        stringstream SS;
                        SS >> message;
                        SS << Type;
                        SS << Level;
                        StorePacket ="<div class=\"system\"><a href=\"?u="+Player1+"\"><span class=\"slot"+PSiD+"\">"+Player1+"</span></a> killed a "+Type+" <span class=\""+Player2+"\">"+Player2+"</span> "+Level+" Rax.</div>";
		}
		else if( type == 23 )
		{
                        string Type;
                        string Level;
                        stringstream SS;
                        SS >> message;
                        SS << Type;
                        SS << Level;
			StorePacket = "<div class=\"system\"><span class=\"sentinel\">Sentinel</span> killed a "+Type+" <span class=\"scourge\">Scourge</span> level "+Level+" Rax.</div>";
		}
                else if( type == 24 )
                {
                        string Type;
                        string Level;
                        stringstream SS;
                        SS >> message;
                        SS << Type;
                        SS << Level;
                        StorePacket = "<div class=\"system\"><span class=\"scourge\">Scourge</span> killed a "+Type+" <span class=\"sentinel\">Sentinel</span> level "+Level+" Rax.</div>";
                }
		else if( type == 25 )
		{
			if( message == "req" )
				StorePacket = "<div class=\"system\"><a href=\"?u="+Player1+"\"><span class=\"slot"+PSiD+"\">"+Player1+"</span></a> requested a swap with <a href=\"?u="+Player2+"\"><span class=\"slot"+VSiD+"\">"+Player2+"</span></a></div>";
			else if( message == "succ" )
				StorePacket = "<div class=\"system\"><a href=\"?u="+Player1+"\"><span class=\"slot"+PSiD+"\">"+Player1+"</span></a> successfully swapped with <a href=\"?u="+Player2+"\"><span class=\"slot"+VSiD+"\">"+Player2+"</span></a></div>";
			else
				CONSOLE_Print( "Unknown Swap Packet was send: " + message );
		}
		else if( type == 26 )
			StorePacket = "<div class=\"system\"><span class=\"scourge\">Scourge's</span> Throne is now on "+message+"%.</div>";
                else if( type == 27 )
                        StorePacket = "<div class=\"system\"><span class=\"sentinel\">Sentinel's</span> Tree of Life is now on "+message+"%.</div>";
		else if( type == 28 )
			StorePacket = "<div class=\"system\"><span class=\""+Player1+"\">"+Player1+"</span> killed Roshan.</div>";
		else if( type == 29 )
		{
			if( message == "pick" )
				StorePacket = "<div class=\"system\"><a href=\"?u="+Player1+"\"><span class=\"slot"+PSiD+"\">"+Player1+"</span></a> picked up Aegis.</div>";
			else if( message == "drop" )
                                StorePacket = "<div class=\"system\"><a href=\"?u="+Player1+"\"><span class=\"slot"+PSiD+"\">"+Player1+"</span></a> dropped Aegis.</div>";
			else
				CONSOLE_Print( "Unknown Aegis Packet was send: " + message );
		}
		else if( type == 30 || type == 31 )
		{
                	string Rune = "unknown";
                        if( message == "1" )
                        	Rune = "Haste";
                        else if( message == "2" )
                        	Rune = "Regeneration";
                        else if( message == "3" )
                                Rune = "Double Damage";
                        else if( message == "4" )
                                Rune = "Illusion";
	                else if( message == "5" )
                                Rune = "Invisible";
			else
				CONSOLE_Print( "Bad Input for RuneType: "+message );

			if( type == 30 )
				StorePacket = "<div class=\"system\"><a href=\"?u="+Player1+"\"><span class=\"slot"+PSiD+"\">"+Player1+"</span></a> stored a <span=\"rune\">"+Rune+"</span> Rune.</div>";
			else if( type == 31 )
                                StorePacket = "<div class=\"system\"><a href=\"?u="+Player1+"\"><span class=\"slot"+PSiD+"\">"+Player1+"</span></a> used a <span=\"rune\">"+Rune+"</span> Rune.</div>";
		}
		else
                        CONSOLE_Print( "Invalid gameprint packet send: "+UTIL_ToString(type)+": "+message );

                m_GameLog.push_back( StorePacket );
        }
	else
		CONSOLE_Print( "Invalid gameprint packet send: "+UTIL_ToString(type)+": "+message );

}

void CBaseGame :: AnnounceEvent( uint32_t RandomNumber )
{
        for( vector<CGamePlayer *> :: iterator i = m_Players.begin( ); i != m_Players.end( ); ++i )
        {
        	uint32_t Level = 0;
                for( vector<CBNET *> :: iterator k = m_GHost->m_BNETs.begin( ); k != m_GHost->m_BNETs.end( ); ++k )
                {
                	if( (*k)->GetServer( ) == (*i)->GetSpoofedRealm( ) )
                        {
                        	Level = (*k)->IsLevel( (*i)->GetName( ) );
                                break;
                        }
		}

		if( Level <= 1 )
			SendChat( (*i)->GetPID( ), "[Announcement] "+m_GHost->m_Announces[RandomNumber] );
        }
//	SendAllChat( "[Announcement] "+m_GHost->m_Announces[RandomNumber] );
}

string CBaseGame :: GetColoredName( string defaultname )
{
	transform( defaultname.begin( ), defaultname.end( ), defaultname.begin( ), (int(*)(int))tolower );
	for( vector<string> :: iterator i = m_GHost->m_ColoredNames.begin( ); i != m_GHost->m_ColoredNames.end( ); )
	{
                string listdefaultname;
                string coloredname;
                stringstream SS;
                SS << *i;
                SS >> listdefaultname;
                SS >> coloredname;

                if( defaultname == listdefaultname )
                        return "|cFF"+coloredname;

		i++;
        }

	return "";
}

