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
#include "commandpacket.h"
#include "ghostdb.h"
#include "bncsutilinterface.h"
#include "bnlsclient.h"
#include "bnetprotocol.h"
#include "bnet.h"
#include "map.h"
#include "packed.h"
#include "savegame.h"
#include "replay.h"
#include "gameprotocol.h"
#include "game_base.h"
#include "gameplayer.h"

#include <boost/filesystem.hpp>

using namespace boost :: filesystem;

//
// CBNET
//

CBNET :: CBNET( CGHost *nGHost, string nServer, string nServerAlias, string nBNLSServer, uint16_t nBNLSPort, uint32_t nBNLSWardenCookie, string nCDKeyROC, string nCDKeyTFT, string nCountryAbbrev, string nCountry, uint32_t nLocaleID, string nUserName, string nUserPassword, string nFirstChannel, char nCommandTrigger, bool nHoldFriends, bool nHoldClan, bool nPublicCommands, unsigned char nWar3Version, BYTEARRAY nEXEVersion, BYTEARRAY nEXEVersionHash, string nPasswordHashType, string nPVPGNRealmName, uint32_t nMaxMessageLength, uint32_t nHostCounterID )
{
	// todotodo: append path seperator to Warcraft3Path if needed

	m_GHost = nGHost;
	m_Socket = new CTCPClient( );
	m_Protocol = new CBNETProtocol( );
	m_BNLSClient = NULL;
	m_BNCSUtil = new CBNCSUtilInterface( nUserName, nUserPassword );
	m_CallablePList = m_GHost->m_DB->ThreadedPList( nServer );
	m_CallableBanList = m_GHost->m_DB->ThreadedBanList( nServer );
	m_CallableTBRemove = m_GHost->m_DB->ThreadedTBRemove( nServer );
	m_Exiting = false;
	m_Server = nServer;
	string LowerServer = m_Server;
	m_AdminLog = vector<string>();
	transform( LowerServer.begin( ), LowerServer.end( ), LowerServer.begin( ), (int(*)(int))tolower );
	m_GHost->m_CheckForFinishedGames = GetTime();
	if( !nServerAlias.empty( ) )
		m_ServerAlias = nServerAlias;
	else if( LowerServer == "useast.battle.net" )
		m_ServerAlias = "USEast";
	else if( LowerServer == "uswest.battle.net" )
		m_ServerAlias = "USWest";
	else if( LowerServer == "asia.battle.net" )
		m_ServerAlias = "Asia";
	else if( LowerServer == "europe.battle.net" )
		m_ServerAlias = "Europe";
	else
		m_ServerAlias = m_Server;

	if( nPasswordHashType == "pvpgn" && !nBNLSServer.empty( ) )
	{
		CONSOLE_Print( "[BNET: " + m_ServerAlias + "] pvpgn connection found with a configured BNLS server, ignoring BNLS server" );
		nBNLSServer.clear( );
		nBNLSPort = 0;
		nBNLSWardenCookie = 0;
	}

	m_BNLSServer = nBNLSServer;
	m_BNLSPort = nBNLSPort;
	m_BNLSWardenCookie = nBNLSWardenCookie;
	m_CDKeyROC = nCDKeyROC;
	m_CDKeyTFT = nCDKeyTFT;

	// remove dashes and spaces from CD keys and convert to uppercase

	m_CDKeyROC.erase( remove( m_CDKeyROC.begin( ), m_CDKeyROC.end( ), '-' ), m_CDKeyROC.end( ) );
	m_CDKeyTFT.erase( remove( m_CDKeyTFT.begin( ), m_CDKeyTFT.end( ), '-' ), m_CDKeyTFT.end( ) );
	m_CDKeyROC.erase( remove( m_CDKeyROC.begin( ), m_CDKeyROC.end( ), ' ' ), m_CDKeyROC.end( ) );
	m_CDKeyTFT.erase( remove( m_CDKeyTFT.begin( ), m_CDKeyTFT.end( ), ' ' ), m_CDKeyTFT.end( ) );
	transform( m_CDKeyROC.begin( ), m_CDKeyROC.end( ), m_CDKeyROC.begin( ), (int(*)(int))toupper );
	transform( m_CDKeyTFT.begin( ), m_CDKeyTFT.end( ), m_CDKeyTFT.begin( ), (int(*)(int))toupper );

	//if( m_CDKeyROC.size( ) != 26 )
	//	CONSOLE_Print( "[BNET: " + m_ServerAlias + "] warning - your ROC CD key is not 26 characters long and is probably invalid" );

	//if( m_GHost->m_TFT && m_CDKeyTFT.size( ) != 26 )
	//	CONSOLE_Print( "[BNET: " + m_ServerAlias + "] warning - your TFT CD key is not 26 characters long and is probably invalid" );

	m_CountryAbbrev = nCountryAbbrev;
	m_Country = nCountry;
	m_LocaleID = nLocaleID;
	m_UserName = nUserName;
	m_UserPassword = nUserPassword;
	m_FirstChannel = nFirstChannel;
	m_CommandTrigger = nCommandTrigger;
	m_War3Version = nWar3Version;
	m_EXEVersion = nEXEVersion;
	m_EXEVersionHash = nEXEVersionHash;
	m_PasswordHashType = nPasswordHashType;
	m_PVPGNRealmName = nPVPGNRealmName;
	m_MaxMessageLength = nMaxMessageLength;
	m_HostCounterID = nHostCounterID;
	m_LastDisconnectedTime = 0;
	m_LastConnectionAttemptTime = 0;
	m_LastNullTime = 0;
	m_LastOutPacketTicks = 0;
	m_LastOutPacketSize = 0;
	m_FrequencyDelayTimes = 0;
	m_LastAdminRefreshTime = GetTime( );
	m_LastBanRefreshTime = GetTime( );
	m_FirstConnect = true;
	m_WaitingToConnect = true;
	m_LoggedIn = false;
	m_InChat = false;
	m_HoldFriends = nHoldFriends;
	m_HoldClan = nHoldClan;
	m_PublicCommands = nPublicCommands;
	m_LastLogUpdateTime = GetTime();
	m_LastXMLFileCreation = GetTime();
	m_XMLUpdate = false;
}

CBNET :: ~CBNET( )
{
	delete m_Socket;
	delete m_Protocol;
	delete m_BNLSClient;

	while( !m_Packets.empty( ) )
	{
		delete m_Packets.front( );
		m_Packets.pop( );
	}

	delete m_BNCSUtil;

	for( vector<CIncomingFriendList *> :: iterator i = m_Friends.begin( ); i != m_Friends.end( ); ++i )
		delete *i;

	for( vector<CIncomingClanList *> :: iterator i = m_Clans.begin( ); i != m_Clans.end( ); ++i )
		delete *i;

        for( vector<PairedRegAdd> :: iterator i = m_PairedRegAdds.begin( ); i != m_PairedRegAdds.end( ); ++i )
                m_GHost->m_Callables.push_back( i->second );

        for( vector<PairedSS> :: iterator i = m_PairedSSs.begin( ); i != m_PairedSSs.end( ); ++i )
                m_GHost->m_Callables.push_back( i->second );

        for( vector<Pairedpm> :: iterator i = m_Pairedpms.begin( ); i != m_Pairedpms.end( ); ++i )
                m_GHost->m_Callables.push_back( i->second );

        for( vector<PairedPassCheck> :: iterator i = m_PairedPassChecks.begin( ); i != m_PairedPassChecks.end( ); ++i )
                m_GHost->m_Callables.push_back( i->second );

        for( vector<Pairedpenp> :: iterator i = m_Pairedpenps.begin( ); i != m_Pairedpenps.end( ); ++i )
                m_GHost->m_Callables.push_back( i->second );

        for( vector<PairedPUp> :: iterator i = m_PairedPUps.begin( ); i != m_PairedPUps.end( ); ++i )
                m_GHost->m_Callables.push_back( i->second );

        for( vector<PairedBanCheck2> :: iterator i = m_PairedBanCheck2s.begin( ); i != m_PairedBanCheck2s.end( ); ++i )
                m_GHost->m_Callables.push_back( i->second );

	for( vector<PairedBanCount> :: iterator i = m_PairedBanCounts.begin( ); i != m_PairedBanCounts.end( ); ++i )
		m_GHost->m_Callables.push_back( i->second );

	for( vector<PairedBanAdd> :: iterator i = m_PairedBanAdds.begin( ); i != m_PairedBanAdds.end( ); ++i )
		m_GHost->m_Callables.push_back( i->second );

	for( vector<PairedBanRemove> :: iterator i = m_PairedBanRemoves.begin( ); i != m_PairedBanRemoves.end( ); ++i )
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

	for( vector<PairedGameUpdate> :: iterator i = m_PairedGameUpdates.begin( ); i != m_PairedGameUpdates.end( ); ++i )
		m_GHost->m_Callables.push_back( i->second );

        if( m_CallablePList )
                m_GHost->m_Callables.push_back( m_CallablePList );

	if( m_CallableBanList )
		m_GHost->m_Callables.push_back( m_CallableBanList );

	if( m_CallableTBRemove )
                m_GHost->m_Callables.push_back( m_CallableTBRemove );

	for( vector<CDBBan *> :: iterator i = m_Bans.begin( ); i != m_Bans.end( ); ++i )
		delete *i;
}

BYTEARRAY CBNET :: GetUniqueName( )
{
	return m_Protocol->GetUniqueName( );
}

unsigned int CBNET :: SetFD( void *fd, void *send_fd, int *nfds )
{
	unsigned int NumFDs = 0;

	if( !m_Socket->HasError( ) && m_Socket->GetConnected( ) )
	{
		m_Socket->SetFD( (fd_set *)fd, (fd_set *)send_fd, nfds );
		++NumFDs;

		if( m_BNLSClient )
			NumFDs += m_BNLSClient->SetFD( fd, send_fd, nfds );
	}

	return NumFDs;
}

std::vector<std::string> &split(const std::string &s, char delim, std::vector<std::string> &elems) {
    std::stringstream ss(s);
    std::string item;
    while (std::getline(ss, item, delim)) {
        elems.push_back(item);
    }
    return elems;
}

std::vector<std::string> split(const std::string &s, char delim) {
    std::vector<std::string> elems;
    split(s, delim, elems);
    return elems;
}

bool CBNET :: Update( void *fd, void *send_fd )
{
	//
	// update callables
	//

        for( vector<PairedRegAdd> :: iterator i = m_PairedRegAdds.begin( ); i != m_PairedRegAdds.end( ); )
        {
                if( i->second->GetReady( ) )
                {
                        uint32_t Result = i->second->GetResult( );
			m_LastRegisterProcess = GetTime( );
			if( Result == 1 )
                                QueueChatCommand( "Your Account has successfully registered on our Datatbase.", i->first, true );
                        else if( Result == 2 )
                                QueueChatCommand( "Your Account was successfully confirmed.", i->first, true );
                        else if( Result == 3 )
                                QueueChatCommand( "Error. Wrong Password.", i->first, true );
                        else if( Result == 4 )
                                QueueChatCommand( "Error. Wrong Email.", i->first, true );
                        else if( Result == 5 )
                                QueueChatCommand( "The User excist already.", i->first, true );
                        else if( Result == 6 )
                                QueueChatCommand( "Error. There is no account to confirm.", i->first, true );
                        else if( Result == 7 )
                                QueueChatCommand( "Error. Somethng missng here O_o.", i->first, true );
                        else
                                QueueChatCommand( "Error. Something gone terrible wrong....", i->first, true );


                        m_GHost->m_DB->RecoverCallable( i->second );
                        delete i->second;
                        i = m_PairedRegAdds.erase( i );
                }
                else
                        ++i;
        }

        for( vector<PairedSS> :: iterator i = m_PairedSSs.begin( ); i != m_PairedSSs.end( ); )
        {
                if( i->second->GetReady( ) )
                {
                        string Result = i->second->GetResult( );
			if( i->second->GetType( ) == "betcheck" )
				QueueChatCommand( "[" + i->second->GetUser( ) + "] Current Points: " + Result, i->first, !i->first.empty( ) );

			if( i->second->GetType( ) == "statsreset" )
			{
				if( Result == "success" )
					QueueChatCommand( "Successfully reseted stats for user ["+i->second->GetUser( )+"]", i->first, !i->first.empty( ) );
				else
					 QueueChatCommand( "No record found for player ["+i->second->GetUser( )+"]", i->first, !i->first.empty( ) );
			}
                        if( i->second->GetType( ) == "aliascheck" )
                        {
                                if( Result != "failed" )
                                        QueueChatCommand( Result, i->first, !i->first.empty( ) );
                                else
                                         QueueChatCommand( "No record found for player ["+i->second->GetUser( )+"]", i->first, !i->first.empty( ) );
                        }
			if( i->second->GetType( ) == "rpp" )
			{
				if( Result != "failed" )
					QueueChatCommand( Result, i->first, !i->first.empty( ) );
				else
					QueueChatCommand( "Something is wrong here.", i->first, !i->first.empty( ) );
			}

                        m_GHost->m_DB->RecoverCallable( i->second );
                        delete i->second;
                        i = m_PairedSSs.erase( i );
                }
                else
                        ++i;
        }

        for( vector<Pairedpm> :: iterator i = m_Pairedpms.begin( ); i != m_Pairedpms.end( ); )
        {
                if( i->second->GetReady( ) )
                {
                        uint32_t Result = i->second->GetResult( );

                        if( Result == -1 )
                                CONSOLE_Print( "Successfully stored a new message ");
                        else if( Result > 0 ) {
                                QueueChatCommand( "Welcome " + i->first + ". You have " + UTIL_ToString( Result ) + " Message(s). Check it out with the command: '!inbox'.", i->first, true );
			}

                        m_GHost->m_DB->RecoverCallable( i->second );
                        delete i->second;
                        i = m_Pairedpms.erase( i );
                }
                else
                        ++i;
        }

        for( vector<PairedPassCheck> :: iterator i = m_PairedPassChecks.begin( ); i != m_PairedPassChecks.end( ); )
        {
                if( i->second->GetReady( ) )
                {
                        uint32_t Result = i->second->GetResult( );
                        if( Result == 1 )
			{
                                if( m_GHost->m_CurrentGame )
                        	{
                                        for( vector<CGamePlayer *> :: iterator k = m_GHost->m_CurrentGame->m_Players.begin( );k != m_GHost->m_CurrentGame->m_Players.end( ); ++k )
                                        {
	                                        CGamePlayer *Player = m_GHost->m_CurrentGame->GetPlayerFromName( (*k)->GetName( ), true );
                                                if( Player )
                                                {
                                                        if( Player->GetName() == i->first )
                                                        {
								CONSOLE_Print( "Successfulley encoded password for: "+Player->GetName() );
                                                                Player->SetPasswordProt( false );
				                        	Player->SetSpoofed( true );
                                                	}
						}
                                        }
                        	}
                        }

                        m_GHost->m_DB->RecoverCallable( i->second );
                        delete i->second;
                        i = m_PairedPassChecks.erase( i );
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
					QueueChatCommand( "User [" + i->second->GetName() + "] has [" + UTIL_ToString( Result ) + "] penality points.", i->first, !i->first.empty( ) );
				else
					QueueChatCommand( "User [" + i->second->GetName() + "] has no penality points.", i->first, !i->first.empty( ) );
			}
                        else if( i->second->GetType( ) == "checkall" )
                        {
                                if( Result != 0 )
                                        QueueChatCommand( "User [" + i->second->GetName() + "] has [" + UTIL_ToString( Result ) + "] penality points.", i->first, !i->first.empty( ) );
                                else
                                        QueueChatCommand( "User [" + i->second->GetName() + "] has no penality points.", i->first, !i->first.empty( ) );
                        }
			else if( i->second->GetType( ) == "add" )
			{
                                if( Result == 1 )
					QueueChatCommand( "Added [" + UTIL_ToString( i->second->GetAmount( ) ) + "] penality point(s) for [" + i->second->GetName() + "] by [" + i->second->GetAdmin() + "]", i->first, !i->first.empty( ) );
                                else if(  Result == 2 )
                                        QueueChatCommand( "User [" + i->second->GetName() + "] got banned for reaching too many penality points", i->first, !i->first.empty( )  );
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

	for( vector<PairedBanCount> :: iterator i = m_PairedBanCounts.begin( ); i != m_PairedBanCounts.end( ); )
	{
		if( i->second->GetReady( ) )
		{
			uint32_t Count = i->second->GetResult( );

			if( Count == 0 )
				QueueChatCommand( m_GHost->m_Language->ThereAreNoBannedUsers( m_Server ), i->first, !i->first.empty( ) );
			else if( Count == 1 )
				QueueChatCommand( m_GHost->m_Language->ThereIsBannedUser( m_Server ), i->first, !i->first.empty( ) );
			else
				QueueChatCommand( m_GHost->m_Language->ThereAreBannedUsers( m_Server, UTIL_ToString( Count ) ), i->first, !i->first.empty( ) );

			m_GHost->m_DB->RecoverCallable( i->second );
			delete i->second;
			i = m_PairedBanCounts.erase( i );
		}
		else
			++i;
	}

        for( vector<PairedBanCheck2> :: iterator i = m_PairedBanCheck2s.begin( ); i != m_PairedBanCheck2s.end( ); )
        {
                if( i->second->GetReady( ) )
                {
			string Result = i->second->GetResult( );
			if( Result == "norec" )
				QueueChatCommand( "There no games played yet on this bot", i->first, !i->first.empty( ) );
			else if( Result == "fail" )
				QueueChatCommand( "Found no banrecords on the latest played IP", i->first, !i->first.empty( ) );
			else
				QueueChatCommand( "Found bans on the last played IPRange: " + Result, i->first, !i->first.empty( ) );

                        m_GHost->m_DB->RecoverCallable( i->second );
                        delete i->second;
                        i = m_PairedBanCheck2s.erase( i );
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
				QueueChatCommand( "Error banning user. User ["+i->second->GetUser( )+"] is already permanent banned." );
			else if( Result == "alreadybannedwithhigheramount" )
				QueueChatCommand( "Error banning user. User ["+i->second->GetUser( )+"] is already banned for a longer amount." );
			else if( !Result.empty( ) && Result != "failed" )
			{
				AddBan( i->second->GetUser( ), i->second->GetIP( ), i->second->GetGameName( ), i->second->GetAdmin( ), i->second->GetReason( ) );
				QueueChatCommand( m_GHost->m_Language->BannedUser( i->second->GetServer( ), i->second->GetUser( ) ), i->first, !i->first.empty( ) );
			}
			else
				QueueChatCommand( "Error, something gone wrong. Please report that to Grief-Code." );

			m_GHost->m_DB->RecoverCallable( i->second );
			delete i->second;
			i = m_PairedBanAdds.erase( i );
		}
		else
			++i;
	}

        for( vector<PairedPUp> :: iterator i = m_PairedPUps.begin( ); i != m_PairedPUps.end( ); )
        {
                if( i->second->GetReady( ) )
                {
                        bool Result = i->second->GetResult( );
                        if( Result )
                                QueueChatCommand( "[" + i->second->GetName( ) + "] is now a [" + GetLevelName( i->second->GetLevel( ) ) + "] on [" + i->second->GetRealm( ) + "]" );
                        else
                                QueueChatCommand( "Error. This User isnt registered on the Database." );

                        m_GHost->m_DB->RecoverCallable( i->second );
                        delete i->second;
                        i = m_PairedPUps.erase( i );
                }
                else
                        ++i;
        }

	for( vector<PairedBanRemove> :: iterator i = m_PairedBanRemoves.begin( ); i != m_PairedBanRemoves.end( ); )
	{
		if( i->second->GetReady( ) )
		{
			if( i->second->GetResult( ) )
			{
				RemoveBan( i->second->GetUser( ) );
				QueueChatCommand( m_GHost->m_Language->UnbannedUser( i->second->GetUser( ) ), i->first, !i->first.empty( ) );
			}
			else
				QueueChatCommand( m_GHost->m_Language->ErrorUnbanningUser( i->second->GetUser( ) ), i->first, !i->first.empty( ) );

			m_GHost->m_DB->RecoverCallable( i->second );
			delete i->second;
			i = m_PairedBanRemoves.erase( i );
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
				string Streak = UTIL_ToString( StatsPlayerSummary->GetStreak( ) );
				if( StatsPlayerSummary->GetStreak( ) < 0 )
					string Streak = "-" + UTIL_ToString( StatsPlayerSummary->GetStreak( ) );

				QueueChatCommand( m_GHost->m_Language->HasPlayedGamesWithThisBot( i->second->GetName( ),
						UTIL_ToString( StatsPlayerSummary->GetScore( ), 0 ),
						UTIL_ToString( StatsPlayerSummary->GetGames( ) ),
						UTIL_ToString( StatsPlayerSummary->GetWinPerc( ), 2 ),
						Streak ) );
			}
			else
				QueueChatCommand( m_GHost->m_Language->HasntPlayedGamesWithThisBot( i->second->GetName( ) ), i->first, !i->first.empty( ) );

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
				QueueChatCommand( "["+i->second->GetName( )+"] Rank: "+StatsPlayerSummary->GetRank( )+" Level: "+UTIL_ToString(IsLevel( i->second->GetName( ) ))+" Class: "+GetLevelName( IsLevel( i->second->GetName( ) ) ), i->first, !i->first.empty( ) );
			else
                                QueueChatCommand( m_GHost->m_Language->HasntPlayedGamesWithThisBot( i->second->GetName( ) ), i->first, !i->first.empty( ) );

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
					QueueChatCommand( "[" + StatsPlayerSummary->GetPlayer( ) + "] Current Streak: " + UTIL_ToString( StatsPlayerSummary->GetStreak( ) ) + " | Max Streak: " + UTIL_ToString( StatsPlayerSummary->GetMaxStreak( ) ) + " | Max LosingStreak: " + UTIL_ToString( StatsPlayerSummary->GetMaxLosingStreak( ) ) );
				else
					QueueChatCommand( "[" + StatsPlayerSummary->GetPlayer( ) + "] Current Streak: -" + UTIL_ToString( StatsPlayerSummary->GetLosingStreak( ) ) + " | Max Streak: " + UTIL_ToString( StatsPlayerSummary->GetMaxStreak( ) ) + " | Max Losing Streak: " + UTIL_ToString( StatsPlayerSummary->GetMaxLosingStreak( ) ) );
			}
                        else
                                QueueChatCommand( m_GHost->m_Language->HasntPlayedGamesWithThisBot( i->second->GetName( ) ), i->first, !i->first.empty( ) );

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

                        if( InboxSummary )
                                QueueChatCommand( "[" + InboxSummary->GetUser( ) + "] " + InboxSummary->GetMessage( ), i->first, true );
                        else
                                QueueChatCommand( "Error. Your Inbox is empty.", i->first, true );

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
				string Summary = m_GHost->m_Language->HasPlayedDotAGamesWithThisBot(	i->second->GetName( ),
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

				QueueChatCommand( Summary, i->first, !i->first.empty( ) );
			}
			else
				QueueChatCommand( m_GHost->m_Language->HasntPlayedDotAGamesWithThisBot( i->second->GetName( ) ), i->first, !i->first.empty( ) );

			m_GHost->m_DB->RecoverCallable( i->second );
			delete i->second;
			i = m_PairedSChecks.erase( i );
		}
		else
			++i;
	}

	for( vector<PairedGameUpdate> :: iterator i = m_PairedGameUpdates.begin( ); i != m_PairedGameUpdates.end( ); )
	{
		if( i->second->GetReady( ) )
		{
			string response = i->second->GetResult( );
                        QueueChatCommand( response, i->first, !i->first.empty( ) );

			m_GHost->m_DB->RecoverCallable( i->second );
			delete i->second;
			i = m_PairedGameUpdates.erase( i );
		}
		else
                        ++i;
	}

	if( GetTime( ) - m_LastLogUpdateTime >= 1800 )
	{
		m_GHost->m_Callables.push_back( m_GHost->m_DB->ThreadedStoreLog( 0, string(), m_AdminLog ) );
		m_AdminLog = vector<string>();
		m_LastLogUpdateTime = GetTime();
	}

	// refresh the permission list every 5 minutes

	if( !m_CallablePList && GetTime( ) - m_LastAdminRefreshTime >= 300 )
	{
		m_CallablePList = m_GHost->m_DB->ThreadedPList( m_Server );
		m_LastAdminRefreshTime = GetTime( );
	}

	// checking for finished games
	if( GetTime( ) - m_GHost->m_CheckForFinishedGames >= 120 && m_GHost->m_StatsUpdate )
	{
#ifdef WIN32
                system("stats.exe");
#else
                system("./stats");
#endif
	        m_GHost->m_CheckForFinishedGames = GetTime();
//	        m_GHost->m_FinishedGames--;
	}

        if( m_CallablePList && m_CallablePList->GetReady( ) )
        {
		m_GHost->LoadDatas();
                m_Permissions = m_CallablePList->GetResult( );
                m_GHost->m_DB->RecoverCallable( m_CallablePList );
                delete m_CallablePList;
                m_CallablePList = NULL;
                m_LastAdminRefreshTime = GetTime( );
        }

	// remove temp bans every 5 min
	// refresh the ban list every 5 minutes

	if( !m_CallableBanList && GetTime( ) - m_LastBanRefreshTime >= 300 )
	{
		m_CallableBanList = m_GHost->m_DB->ThreadedBanList( m_Server );
		m_CallableTBRemove = m_GHost->m_DB->ThreadedTBRemove( m_Server );
	}

	if( m_CallableBanList && m_CallableBanList->GetReady( ) && m_CallableTBRemove && m_CallableTBRemove->GetReady( ) )
	{
		// CONSOLE_Print( "[BNET: " + m_ServerAlias + "] refreshed ban list (" + UTIL_ToString( m_Bans.size( ) ) + " -> " + UTIL_ToString( m_CallableBanList->GetResult( ).size( ) ) + " bans)" );

		for( vector<CDBBan *> :: iterator i = m_Bans.begin( ); i != m_Bans.end( ); ++i )
			delete *i;

		m_Bans = m_CallableBanList->GetResult( );
		m_GHost->m_DB->RecoverCallable( m_CallableBanList );
		delete m_CallableBanList;
		m_CallableBanList = NULL;
                m_GHost->m_DB->RecoverCallable( m_CallableTBRemove );
                delete m_CallableTBRemove;
                m_CallableTBRemove = NULL;
		m_LastBanRefreshTime = GetTime( );
	}

	// we return at the end of each if statement so we don't have to deal with errors related to the order of the if statements
	// that means it might take a few ms longer to complete a task involving multiple steps (in this case, reconnecting) due to blocking or sleeping
	// but it's not a big deal at all, maybe 100ms in the worst possible case (based on a 50ms blocking time)

	if( m_Socket->HasError( ) )
	{
		// the socket has an error

		CONSOLE_Print( "[BNET: " + m_ServerAlias + "] disconnected from battle.net due to socket error" );

		if( m_Socket->GetError( ) == ECONNRESET && GetTime( ) - m_LastConnectionAttemptTime <= 15 )
			CONSOLE_Print( "[BNET: " + m_ServerAlias + "] warning - you are probably temporarily IP banned from battle.net" );

		CONSOLE_Print( "[BNET: " + m_ServerAlias + "] waiting 90 seconds to reconnect" );
		m_GHost->EventBNETDisconnected( this );
		delete m_BNLSClient;
		m_BNLSClient = NULL;
		m_BNCSUtil->Reset( m_UserName, m_UserPassword );
		m_Socket->Reset( );
		m_LastDisconnectedTime = GetTime( );
		m_LoggedIn = false;
		m_InChat = false;
		m_WaitingToConnect = true;
		return m_Exiting;
	}

	if( !m_Socket->GetConnecting( ) && !m_Socket->GetConnected( ) && !m_WaitingToConnect )
	{
		// the socket was disconnected

		CONSOLE_Print( "[BNET: " + m_ServerAlias + "] disconnected from battle.net" );
		CONSOLE_Print( "[BNET: " + m_ServerAlias + "] waiting 90 seconds to reconnect" );
		m_GHost->EventBNETDisconnected( this );
		delete m_BNLSClient;
		m_BNLSClient = NULL;
		m_BNCSUtil->Reset( m_UserName, m_UserPassword );
		m_Socket->Reset( );
		m_LastDisconnectedTime = GetTime( );
		m_LoggedIn = false;
		m_InChat = false;
		m_WaitingToConnect = true;
		return m_Exiting;
	}

	if( m_Socket->GetConnected( ) )
	{
		// the socket is connected and everything appears to be working properly

		m_Socket->DoRecv( (fd_set *)fd );
		ExtractPackets( );
		ProcessPackets( );

		// update the BNLS client

		if( m_BNLSClient )
		{
			if( m_BNLSClient->Update( fd, send_fd ) )
			{
				CONSOLE_Print( "[BNET: " + m_ServerAlias + "] deleting BNLS client" );
				delete m_BNLSClient;
				m_BNLSClient = NULL;
			}
			else
			{
				BYTEARRAY WardenResponse = m_BNLSClient->GetWardenResponse( );

				if( !WardenResponse.empty( ) )
					m_Socket->PutBytes( m_Protocol->SEND_SID_WARDEN( WardenResponse ) );
			}
		}

		// check if at least one packet is waiting to be sent and if we've waited long enough to prevent flooding
		// this formula has changed many times but currently we wait 1 second if the last packet was "small", 3.5 seconds if it was "medium", and 4 seconds if it was "big"

		uint32_t WaitTicks = 0;

		if( m_LastOutPacketSize < 10 )
			WaitTicks = 1300;
		else if( m_LastOutPacketSize < 30 )
			WaitTicks = 3400;
		else if( m_LastOutPacketSize < 50 )
			WaitTicks = 3600;
		else if( m_LastOutPacketSize < 100 )
			WaitTicks = 3900;
		else
			WaitTicks = 5500;

		// add on frequency delay

		WaitTicks += m_FrequencyDelayTimes * 60;

		if( !m_OutPackets.empty( ) && GetTicks( ) - m_LastOutPacketTicks >= WaitTicks )
		{
			if( m_OutPackets.size( ) > 7 )
				CONSOLE_Print( "[BNET: " + m_ServerAlias + "] packet queue warning - there are " + UTIL_ToString( m_OutPackets.size( ) ) + " packets waiting to be sent" );

			m_Socket->PutBytes( m_OutPackets.front( ) );
			m_LastOutPacketSize = m_OutPackets.front( ).size( );
			m_OutPackets.pop( );

			// reset frequency delay (or increment it)

			if( m_FrequencyDelayTimes >= 100 || GetTicks( ) > m_LastOutPacketTicks + WaitTicks + 500 )
				m_FrequencyDelayTimes = 0;
			else
				m_FrequencyDelayTimes++;

			m_LastOutPacketTicks = GetTicks( );
		}

		// send a null packet every 60 seconds to detect disconnects

		if( GetTime( ) - m_LastNullTime >= 60 && GetTicks( ) - m_LastOutPacketTicks >= 60000 )
		{
			m_Socket->PutBytes( m_Protocol->SEND_SID_NULL( ) );
			m_LastNullTime = GetTime( );
		}

		m_Socket->DoSend( (fd_set *)send_fd );
		return m_Exiting;
	}

	if( m_Socket->GetConnecting( ) )
	{
		// we are currently attempting to connect to battle.net

		if( m_Socket->CheckConnect( ) )
		{
			// the connection attempt completed

			CONSOLE_Print( "[BNET: " + m_ServerAlias + "] connected" );
			m_GHost->EventBNETConnected( this );
			m_Socket->PutBytes( m_Protocol->SEND_PROTOCOL_INITIALIZE_SELECTOR( ) );
			m_Socket->PutBytes( m_Protocol->SEND_SID_AUTH_INFO( m_War3Version, m_GHost->m_TFT, m_LocaleID, m_CountryAbbrev, m_Country ) );
			m_Socket->DoSend( (fd_set *)send_fd );
			m_LastNullTime = GetTime( );
			m_LastOutPacketTicks = GetTicks( );

			while( !m_OutPackets.empty( ) )
				m_OutPackets.pop( );

			return m_Exiting;
		}
		else if( GetTime( ) - m_LastConnectionAttemptTime >= 15 )
		{
			// the connection attempt timed out (15 seconds)

			CONSOLE_Print( "[BNET: " + m_ServerAlias + "] connect timed out" );
			CONSOLE_Print( "[BNET: " + m_ServerAlias + "] waiting 90 seconds to reconnect" );
			m_GHost->EventBNETConnectTimedOut( this );
			m_Socket->Reset( );
			m_LastDisconnectedTime = GetTime( );
			m_WaitingToConnect = true;
			return m_Exiting;
		}
	}

	if( !m_Socket->GetConnecting( ) && !m_Socket->GetConnected( ) && ( m_FirstConnect || GetTime( ) - m_LastDisconnectedTime >= 90 ) )
	{
		// attempt to connect to battle.net

		m_FirstConnect = false;
		CONSOLE_Print( "[BNET: " + m_ServerAlias + "] connecting to server [" + m_Server + "] on port 6112" );
		m_GHost->EventBNETConnecting( this );

		if( !m_GHost->m_BindAddress.empty( ) )
			CONSOLE_Print( "[BNET: " + m_ServerAlias + "] attempting to bind to address [" + m_GHost->m_BindAddress + "]" );

		if( m_ServerIP.empty( ) )
		{
			m_Socket->Connect( m_GHost->m_BindAddress, m_Server, 6112 );

			if( !m_Socket->HasError( ) )
			{
				m_ServerIP = m_Socket->GetIPString( );
				CONSOLE_Print( "[BNET: " + m_ServerAlias + "] resolved and cached server IP address " + m_ServerIP );
			}
		}
		else
		{
			// use cached server IP address since resolving takes time and is blocking

			CONSOLE_Print( "[BNET: " + m_ServerAlias + "] using cached server IP address " + m_ServerIP );
			m_Socket->Connect( m_GHost->m_BindAddress, m_ServerIP, 6112 );
		}

		m_WaitingToConnect = false;
		m_LastConnectionAttemptTime = GetTime( );
		return m_Exiting;
	}

	return m_Exiting;
}

void CBNET :: ExtractPackets( )
{
	// extract as many packets as possible from the socket's receive buffer and put them in the m_Packets queue

	string *RecvBuffer = m_Socket->GetBytes( );
	BYTEARRAY Bytes = UTIL_CreateByteArray( (unsigned char *)RecvBuffer->c_str( ), RecvBuffer->size( ) );

	// a packet is at least 4 bytes so loop as long as the buffer contains 4 bytes

	while( Bytes.size( ) >= 4 )
	{
		// byte 0 is always 255

		if( Bytes[0] == BNET_HEADER_CONSTANT )
		{
			// bytes 2 and 3 contain the length of the packet

			uint16_t Length = UTIL_ByteArrayToUInt16( Bytes, false, 2 );

			if( Length >= 4 )
			{
				if( Bytes.size( ) >= Length )
				{
					m_Packets.push( new CCommandPacket( BNET_HEADER_CONSTANT, Bytes[1], BYTEARRAY( Bytes.begin( ), Bytes.begin( ) + Length ) ) );
					*RecvBuffer = RecvBuffer->substr( Length );
					Bytes = BYTEARRAY( Bytes.begin( ) + Length, Bytes.end( ) );
				}
				else
					return;
			}
			else
			{
				CONSOLE_Print( "[BNET: " + m_ServerAlias + "] error - received invalid packet from battle.net (bad length), disconnecting" );
				m_Socket->Disconnect( );
				return;
			}
		}
		else
		{
			CONSOLE_Print( "[BNET: " + m_ServerAlias + "] error - received invalid packet from battle.net (bad header constant), disconnecting" );
			m_Socket->Disconnect( );
			return;
		}
	}
}

void CBNET :: ProcessPackets( )
{
	CIncomingGameHost *GameHost = NULL;
	CIncomingChatEvent *ChatEvent = NULL;
	BYTEARRAY WardenData;
	vector<CIncomingFriendList *> Friends;
	vector<CIncomingClanList *> Clans;

	// process all the received packets in the m_Packets queue
	// this normally means sending some kind of response

	while( !m_Packets.empty( ) )
	{
		CCommandPacket *Packet = m_Packets.front( );
		m_Packets.pop( );

		if( Packet->GetPacketType( ) == BNET_HEADER_CONSTANT )
		{
			switch( Packet->GetID( ) )
			{
			case CBNETProtocol :: SID_NULL:
				// warning: we do not respond to NULL packets with a NULL packet of our own
				// this is because PVPGN servers are programmed to respond to NULL packets so it will create a vicious cycle of useless traffic
				// official battle.net servers do not respond to NULL packets

				m_Protocol->RECEIVE_SID_NULL( Packet->GetData( ) );
				break;

			case CBNETProtocol :: SID_GETADVLISTEX:
				GameHost = m_Protocol->RECEIVE_SID_GETADVLISTEX( Packet->GetData( ) );

				//if( GameHost )
				//	CONSOLE_Print( "[BNET: " + m_ServerAlias + "] joining game [" + GameHost->GetGameName( ) + "]" );

				delete GameHost;
				GameHost = NULL;
				break;

			case CBNETProtocol :: SID_ENTERCHAT:
				if( m_Protocol->RECEIVE_SID_ENTERCHAT( Packet->GetData( ) ) )
				{
					CONSOLE_Print( "[BNET: " + m_ServerAlias + "] joining channel [" + m_FirstChannel + "]" );
					m_InChat = true;
					m_Socket->PutBytes( m_Protocol->SEND_SID_JOINCHANNEL( m_FirstChannel ) );
				}

				break;

			case CBNETProtocol :: SID_CHATEVENT:
				ChatEvent = m_Protocol->RECEIVE_SID_CHATEVENT( Packet->GetData( ) );

				if( ChatEvent )
					ProcessChatEvent( ChatEvent );

				delete ChatEvent;
				ChatEvent = NULL;
				break;

			case CBNETProtocol :: SID_CHECKAD:
				m_Protocol->RECEIVE_SID_CHECKAD( Packet->GetData( ) );
				break;

			case CBNETProtocol :: SID_STARTADVEX3:
				if( m_Protocol->RECEIVE_SID_STARTADVEX3( Packet->GetData( ) ) )
				{
					m_InChat = false;
					m_GHost->EventBNETGameRefreshed( this );
				}
				else
				{
					CONSOLE_Print( "[BNET: " + m_ServerAlias + "] startadvex3 failed" );
					m_GHost->EventBNETGameRefreshFailed( this );
				}

				break;

			case CBNETProtocol :: SID_PING:
				m_Socket->PutBytes( m_Protocol->SEND_SID_PING( m_Protocol->RECEIVE_SID_PING( Packet->GetData( ) ) ) );
				break;

			case CBNETProtocol :: SID_AUTH_INFO:
				if( m_Protocol->RECEIVE_SID_AUTH_INFO( Packet->GetData( ) ) )
				{
					if( m_BNCSUtil->HELP_SID_AUTH_CHECK( m_GHost->m_TFT, m_GHost->m_Warcraft3Path, m_CDKeyROC, m_CDKeyTFT, m_Protocol->GetValueStringFormulaString( ), m_Protocol->GetIX86VerFileNameString( ), m_Protocol->GetClientToken( ), m_Protocol->GetServerToken( ) ) )
					{
						// override the exe information generated by bncsutil if specified in the config file
						// apparently this is useful for pvpgn users

						if( m_EXEVersion.size( ) == 4 )
						{
							CONSOLE_Print( "[BNET: " + m_ServerAlias + "] using custom exe version bnet_custom_exeversion = " + UTIL_ToString( m_EXEVersion[0] ) + " " + UTIL_ToString( m_EXEVersion[1] ) + " " + UTIL_ToString( m_EXEVersion[2] ) + " " + UTIL_ToString( m_EXEVersion[3] ) );
							m_BNCSUtil->SetEXEVersion( m_EXEVersion );
						}

						if( m_EXEVersionHash.size( ) == 4 )
						{
							CONSOLE_Print( "[BNET: " + m_ServerAlias + "] using custom exe version hash bnet_custom_exeversionhash = " + UTIL_ToString( m_EXEVersionHash[0] ) + " " + UTIL_ToString( m_EXEVersionHash[1] ) + " " + UTIL_ToString( m_EXEVersionHash[2] ) + " " + UTIL_ToString( m_EXEVersionHash[3] ) );
							m_BNCSUtil->SetEXEVersionHash( m_EXEVersionHash );
						}

						if( m_GHost->m_TFT )
							CONSOLE_Print( "[BNET: " + m_ServerAlias + "] attempting to auth as Warcraft III: The Frozen Throne" );
						else
							CONSOLE_Print( "[BNET: " + m_ServerAlias + "] attempting to auth as Warcraft III: Reign of Chaos" );

						m_Socket->PutBytes( m_Protocol->SEND_SID_AUTH_CHECK( m_GHost->m_TFT, m_Protocol->GetClientToken( ), m_BNCSUtil->GetEXEVersion( ), m_BNCSUtil->GetEXEVersionHash( ), m_BNCSUtil->GetKeyInfoROC( ), m_BNCSUtil->GetKeyInfoTFT( ), m_BNCSUtil->GetEXEInfo( ), "GHost" ) );

						// the Warden seed is the first 4 bytes of the ROC key hash
						// initialize the Warden handler

						if( !m_BNLSServer.empty( ) )
						{
							CONSOLE_Print( "[BNET: " + m_ServerAlias + "] creating BNLS client" );
							delete m_BNLSClient;
							m_BNLSClient = new CBNLSClient( m_BNLSServer, m_BNLSPort, m_BNLSWardenCookie );
							m_BNLSClient->QueueWardenSeed( UTIL_ByteArrayToUInt32( m_BNCSUtil->GetKeyInfoROC( ), false, 16 ) );
						}
					}
					else
					{
						CONSOLE_Print( "[BNET: " + m_ServerAlias + "] logon failed - bncsutil key hash failed (check your Warcraft 3 path and cd keys), disconnecting" );
						m_Socket->Disconnect( );
						delete Packet;
						return;
					}
				}

				break;

			case CBNETProtocol :: SID_AUTH_CHECK:
				if( m_Protocol->RECEIVE_SID_AUTH_CHECK( Packet->GetData( ) ) )
				{
					// cd keys accepted

					CONSOLE_Print( "[BNET: " + m_ServerAlias + "] cd keys accepted" );
					m_BNCSUtil->HELP_SID_AUTH_ACCOUNTLOGON( );
					m_Socket->PutBytes( m_Protocol->SEND_SID_AUTH_ACCOUNTLOGON( m_BNCSUtil->GetClientKey( ), m_UserName ) );
				}
				else
				{
					// cd keys not accepted

					switch( UTIL_ByteArrayToUInt32( m_Protocol->GetKeyState( ), false ) )
					{
					case CBNETProtocol :: KR_ROC_KEY_IN_USE:
						CONSOLE_Print( "[BNET: " + m_ServerAlias + "] logon failed - ROC CD key in use by user [" + m_Protocol->GetKeyStateDescription( ) + "], disconnecting" );
						break;
					case CBNETProtocol :: KR_TFT_KEY_IN_USE:
						CONSOLE_Print( "[BNET: " + m_ServerAlias + "] logon failed - TFT CD key in use by user [" + m_Protocol->GetKeyStateDescription( ) + "], disconnecting" );
						break;
					case CBNETProtocol :: KR_OLD_GAME_VERSION:
						CONSOLE_Print( "[BNET: " + m_ServerAlias + "] logon failed - game version is too old, disconnecting" );
						break;
					case CBNETProtocol :: KR_INVALID_VERSION:
						CONSOLE_Print( "[BNET: " + m_ServerAlias + "] logon failed - game version is invalid, disconnecting" );
						break;
					default:
						CONSOLE_Print( "[BNET: " + m_ServerAlias + "] logon failed - cd keys not accepted, disconnecting" );
						break;
					}

					m_Socket->Disconnect( );
					delete Packet;
					return;
				}

				break;

			case CBNETProtocol :: SID_AUTH_ACCOUNTLOGON:
				if( m_Protocol->RECEIVE_SID_AUTH_ACCOUNTLOGON( Packet->GetData( ) ) )
				{
					CONSOLE_Print( "[BNET: " + m_ServerAlias + "] username [" + m_UserName + "] accepted" );

					if( m_PasswordHashType == "pvpgn" )
					{
						// pvpgn logon

						CONSOLE_Print( "[BNET: " + m_ServerAlias + "] using pvpgn logon type (for pvpgn servers only)" );
						m_BNCSUtil->HELP_PvPGNPasswordHash( m_UserPassword );
						m_Socket->PutBytes( m_Protocol->SEND_SID_AUTH_ACCOUNTLOGONPROOF( m_BNCSUtil->GetPvPGNPasswordHash( ) ) );
					}
					else
					{
						// battle.net logon

						CONSOLE_Print( "[BNET: " + m_ServerAlias + "] using battle.net logon type (for official battle.net servers only)" );
						m_BNCSUtil->HELP_SID_AUTH_ACCOUNTLOGONPROOF( m_Protocol->GetSalt( ), m_Protocol->GetServerPublicKey( ) );
						m_Socket->PutBytes( m_Protocol->SEND_SID_AUTH_ACCOUNTLOGONPROOF( m_BNCSUtil->GetM1( ) ) );
					}
				}
				else
				{
					CONSOLE_Print( "[BNET: " + m_ServerAlias + "] logon failed - invalid username, disconnecting" );
					m_Socket->Disconnect( );
					delete Packet;
					return;
				}

				break;

			case CBNETProtocol :: SID_AUTH_ACCOUNTLOGONPROOF:
				if( m_Protocol->RECEIVE_SID_AUTH_ACCOUNTLOGONPROOF( Packet->GetData( ) ) )
				{
					// logon successful

					CONSOLE_Print( "[BNET: " + m_ServerAlias + "] logon successful" );
					m_LoggedIn = true;
					m_GHost->EventBNETLoggedIn( this );
					m_Socket->PutBytes( m_Protocol->SEND_SID_NETGAMEPORT( m_GHost->m_HostPort ) );
					m_Socket->PutBytes( m_Protocol->SEND_SID_ENTERCHAT( ) );
					m_Socket->PutBytes( m_Protocol->SEND_SID_FRIENDSLIST( ) );
					m_Socket->PutBytes( m_Protocol->SEND_SID_CLANMEMBERLIST( ) );
				}
				else
				{
					CONSOLE_Print( "[BNET: " + m_ServerAlias + "] logon failed - invalid password, disconnecting" );

					// try to figure out if the user might be using the wrong logon type since too many people are confused by this

					string Server = m_Server;
					transform( Server.begin( ), Server.end( ), Server.begin( ), (int(*)(int))tolower );

					if( m_PasswordHashType == "pvpgn" && ( Server == "useast.battle.net" || Server == "uswest.battle.net" || Server == "asia.battle.net" || Server == "europe.battle.net" ) )
						CONSOLE_Print( "[BNET: " + m_ServerAlias + "] it looks like you're trying to connect to a battle.net server using a pvpgn logon type, check your config file's \"battle.net custom data\" section" );
					else if( m_PasswordHashType != "pvpgn" && ( Server != "useast.battle.net" && Server != "uswest.battle.net" && Server != "asia.battle.net" && Server != "europe.battle.net" ) )
						CONSOLE_Print( "[BNET: " + m_ServerAlias + "] it looks like you're trying to connect to a pvpgn server using a battle.net logon type, check your config file's \"battle.net custom data\" section" );

					m_Socket->Disconnect( );
					delete Packet;
					return;
				}

				break;

			case CBNETProtocol :: SID_WARDEN:
				WardenData = m_Protocol->RECEIVE_SID_WARDEN( Packet->GetData( ) );

				if( m_BNLSClient )
					m_BNLSClient->QueueWardenRaw( WardenData );
				else
					CONSOLE_Print( "[BNET: " + m_ServerAlias + "] warning - received warden packet but no BNLS server is available, you will be kicked from battle.net soon" );

				break;

			case CBNETProtocol :: SID_FRIENDSLIST:
				Friends = m_Protocol->RECEIVE_SID_FRIENDSLIST( Packet->GetData( ) );

				for( vector<CIncomingFriendList *> :: iterator i = m_Friends.begin( ); i != m_Friends.end( ); ++i )
					delete *i;

				m_Friends = Friends;
				break;

			case CBNETProtocol :: SID_CLANMEMBERLIST:
				vector<CIncomingClanList *> Clans = m_Protocol->RECEIVE_SID_CLANMEMBERLIST( Packet->GetData( ) );

				for( vector<CIncomingClanList *> :: iterator i = m_Clans.begin( ); i != m_Clans.end( ); ++i )
					delete *i;

				m_Clans = Clans;
				break;
			}
		}

		delete Packet;
	}
}

void CBNET :: ProcessChatEvent( CIncomingChatEvent *chatEvent )
{
	CBNETProtocol :: IncomingChatEvent Event = chatEvent->GetChatEvent( );
	bool Whisper = ( Event == CBNETProtocol :: EID_WHISPER );
	string User = chatEvent->GetUser( );
	string Message = chatEvent->GetMessage( );

	if( Event == CBNETProtocol :: EID_WHISPER || Event == CBNETProtocol :: EID_TALK )
	{
		if( Event == CBNETProtocol :: EID_WHISPER )
		{
			CONSOLE_Print( "[WHISPER: " + m_ServerAlias + "] [" + User + "] " + Message );
			m_GHost->EventBNETWhisper( this, User, Message );
		}
		else
		{
			CONSOLE_Print( "[LOCAL: " + m_ServerAlias + "] [" + User + "] " + Message );
			m_GHost->EventBNETChat( this, User, Message );
		}

		// handle spoof checking for current game
		// this case covers whispers - we assume that anyone who sends a whisper to the bot with message "spoofcheck" should be considered spoof checked
		// note that this means you can whisper "spoofcheck" even in a public game to manually spoofcheck if the /whois fails

		if( Event == CBNETProtocol :: EID_WHISPER && m_GHost->m_CurrentGame )
		{
			if( Message == "s" || Message == "sc" || Message == "spoof" || Message == "check" || Message == "spoofcheck" )
				m_GHost->m_CurrentGame->AddToSpoofed( m_Server, User, true );
			else if( Message.find( m_GHost->m_CurrentGame->GetGameName( ) ) != string :: npos )
			{
				// look for messages like "entered a Warcraft III The Frozen Throne game called XYZ"
				// we don't look for the English part of the text anymore because we want this to work with multiple languages
				// it's a pretty safe bet that anyone whispering the bot with a message containing the game name is a valid spoofcheck

				if( m_PasswordHashType == "pvpgn" && User == m_PVPGNRealmName )
				{
					// the equivalent pvpgn message is: [PvPGN Realm] Your friend abc has entered a Warcraft III Frozen Throne game named "xyz".

					vector<string> Tokens = UTIL_Tokenize( Message, ' ' );

					if( Tokens.size( ) >= 3 )
						m_GHost->m_CurrentGame->AddToSpoofed( m_Server, Tokens[2], false );
				}
				else
					m_GHost->m_CurrentGame->AddToSpoofed( m_Server, User, false );
			}
		}

		// handle bot commands

		if( Message == "?trigger" && ( IsLevel( User ) >= 9 || ( m_PublicCommands && m_OutPackets.size( ) <= 3 ) ) )
			QueueChatCommand( m_GHost->m_Language->CommandTrigger( string( 1, m_CommandTrigger ) ), User, Whisper );
		else if( !Message.empty( ) && Message[0] == m_CommandTrigger )
		{
	            BotCommand( Message, User, Whisper, false );
		}
	}
	else if( Event == CBNETProtocol :: EID_CHANNEL )
	{
		// keep track of current channel so we can rejoin it after hosting a game
		CONSOLE_Print( "[BNET: " + m_ServerAlias + "] joined channel [" + Message + "]" );
		//BotCommand( "!g", "XML", Whisper, true );
		m_CurrentChannel = Message;
	}
	else if( Event == CBNETProtocol :: EID_INFO )
	{
		CONSOLE_Print( "[INFO: " + m_ServerAlias + "] " + Message );

		// extract the first word which we hope is the username
		// this is not necessarily true though since info messages also include channel MOTD's and such

		string UserName;
		string :: size_type Split = Message.find( " " );

		if( Split != string :: npos )
			UserName = Message.substr( 0, Split );
		else
			UserName = Message.substr( 0 );

		// handle spoof checking for current game
		// this case covers whois results which are used when hosting a public game (we send out a "/whois [player]" for each player)
		// at all times you can still /w the bot with "spoofcheck" to manually spoof check

		if( m_GHost->m_CurrentGame && m_GHost->m_CurrentGame->GetPlayerFromName( UserName, true ) )
		{
			if( Message.find( "is away" ) != string :: npos )
				m_GHost->m_CurrentGame->SendAllChat( m_GHost->m_Language->SpoofPossibleIsAway( UserName ) );
			else if( Message.find( "is unavailable" ) != string :: npos )
				m_GHost->m_CurrentGame->SendAllChat( m_GHost->m_Language->SpoofPossibleIsUnavailable( UserName ) );
			else if( Message.find( "is refusing messages" ) != string :: npos )
				m_GHost->m_CurrentGame->SendAllChat( m_GHost->m_Language->SpoofPossibleIsRefusingMessages( UserName ) );
			else if( Message.find( "is using Warcraft III The Frozen Throne in the channel" ) != string :: npos )
				m_GHost->m_CurrentGame->SendAllChat( m_GHost->m_Language->SpoofDetectedIsNotInGame( UserName ) );
			else if( Message.find( "is using Warcraft III The Frozen Throne in channel" ) != string :: npos )
				m_GHost->m_CurrentGame->SendAllChat( m_GHost->m_Language->SpoofDetectedIsNotInGame( UserName ) );
			else if( Message.find( "is using Warcraft III The Frozen Throne in a private channel" ) != string :: npos )
				m_GHost->m_CurrentGame->SendAllChat( m_GHost->m_Language->SpoofDetectedIsInPrivateChannel( UserName ) );

			if( Message.find( "is using Warcraft III The Frozen Throne in game" ) != string :: npos || Message.find( "is using Warcraft III Frozen Throne and is currently in  game" ) != string :: npos )
			{

				// check both the current game name and the last game name against the /whois response
				// this is because when the game is rehosted, players who joined recently will be in the previous game according to battle.net
				// note: if the game is rehosted more than once it is possible (but unlikely) for a false positive because only two game names are checked

				if( Message.find( m_GHost->m_CurrentGame->GetGameName( ) ) != string :: npos || Message.find( m_GHost->m_CurrentGame->GetLastGameName( ) ) != string :: npos )
					m_GHost->m_CurrentGame->AddToSpoofed( m_Server, UserName, false );
				else
					m_GHost->m_CurrentGame->SendAllChat( m_GHost->m_Language->SpoofDetectedIsInAnotherGame( UserName ) );
 			}
		}
	}

	else if( Event == CBNETProtocol :: EID_JOIN ) {
		m_Pairedpms.push_back( Pairedpm( User, m_GHost->m_DB->Threadedpm( User, string(), 0, string(), "join" ) ) );
		CONSOLE_Print( "[BNET: " + m_ServerAlias + "] user [" + User + "] joined channel " + m_CurrentChannel );
	}
	else if( Event == CBNETProtocol :: EID_ERROR )
		CONSOLE_Print( "[ERROR: " + m_ServerAlias + "] " + Message );
	else if( Event == CBNETProtocol :: EID_EMOTE )
	{
		CONSOLE_Print( "[EMOTE: " + m_ServerAlias + "] [" + User + "] " + Message );
		m_GHost->EventBNETEmote( this, User, Message );
	}
}

void CBNET :: BotCommand(string Message, string User, bool Whisper, bool ForceRoot )
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

			if( IsLevel( User ) >= 5 || ForceRoot )
			{

				string level = GetLevelName( IsLevel( User ) );
                                CONSOLE_Print( "[BNET] "+ level +" [" + User + "] sent command [" + Command + "] with payload [" + Payload + "]" );

        	                //save admin log
				m_AdminLog.push_back( User + " cl" + "\t" + UTIL_ToString( IsLevel( User ) ) + "\t" + Command + "\t" + Payload );

				/**************************************
				* GRIEF-CODE COMMANDS | RCON COMMANDS *
				**************************************/

				//
				// !RCON
				//

				if( Command == "rcon" )
				{
		                        string RCONCommand;
                		        string RCONPayload;
                        		string :: size_type PayloadStart = Payload.find( " " );

	        	                if( PayloadStart != string :: npos )
        	        	        {
                                		RCONCommand = Payload.substr( 0, PayloadStart );
                                		RCONPayload = Payload.substr( PayloadStart + 1 );
                        		}
                        		else
                                		RCONCommand = Payload.substr( 1 );

		                        transform( RCONCommand.begin( ), RCONCommand.end( ), RCONCommand.begin( ), (int(*)(int))tolower );

					//
					// !MUTE
					//
					if( RCONCommand == "mute" )
					{
						bool Success = false;
						string User;
        	                                string Name;
                	                        stringstream SS;
                        	                SS << RCONPayload;
						SS >> User;
	                                        if( SS.fail( ) )
                                                       CONSOLE_Print( "[BNET: " + m_ServerAlias + "] bad input #1 to rcon-mute command" );
                                                else
						{
	                                        	SS >> Name;
                	                                if( SS.fail( ) )
		         	                               CONSOLE_Print( "[BNET: " + m_ServerAlias + "] bad input #2 to rcon-mute command" );
                                	                else
                                        	        {

			                                        if( m_GHost->m_CurrentGame )
        			                                {
									for( vector<CGamePlayer *> :: iterator k = m_GHost->m_CurrentGame->m_Players.begin( ); k != m_GHost->m_CurrentGame->m_Players.end( ); ++k )
                	        		                        {
                                			                	CGamePlayer *Player = m_GHost->m_CurrentGame->GetPlayerFromName( (*k)->GetName( ), true );
                                        	        		        if( Player )
                                                	        		{
		                                                        		if( Player->GetName() == Name )
											{
                                		                                		m_GHost->m_CurrentGame->SendAllChat( "[" + Name + "] got muted by [" + User + "] using remote command"   );
												Player->SetMuted( true );
												Success = true;
											}
		                        	                                }
                		                	                }
                                		        	}
	                                        		for( vector<CBaseGame *> :: iterator i = m_GHost->m_Games.begin( ); i != m_GHost->m_Games.end( ); ++i )
		        	                                {
	        			                                for( vector<CGamePlayer *> :: iterator k = (*i)->m_Players.begin( ); k != (*i)->m_Players.end( ); ++k )
                        			                        {
                                	        		        	CGamePlayer *Player = (*i)->GetPlayerFromName( (*k)->GetName( ), true );
                                        	                		if( Player )
		                                                	        {
                		                                        		if( Player->GetName() == Name )
											{
		                                		                                (*i)->SendAllChat( "[" + Name + "] got muted by [" + User + "] using remote command"   );
												Player->SetMuted( true );
												Success = true;
											}
                		                	                	}
                                		        		}
                                        			}
							}
						}
/*
						if( Success )
							QueueChatCommand( "Successfully muted [" + Name + "]" );
						else
							QueueChatCommand( "Could not find [" + Name + "] in any hosted game" );
*/
					}

	                                //
        	                        // !UNMUTE
                	                //
		                        else if( RCONCommand == "unmute" )
                	                {
                        	                bool Success = false;
                                	        string User;
                                        	string Name;
	                                        stringstream SS;
        	                                SS << RCONPayload;
                	                        SS >> User;
                                                if( SS.fail( ) )
                                                       CONSOLE_Print( "[BNET: " + m_ServerAlias + "] bad input #1 to rcon-unmute command" );
                                                else
                                                {
                                                        SS >> Name;
                                                        if( SS.fail( ) )
                                                               CONSOLE_Print( "[BNET: " + m_ServerAlias + "] bad input #2 to rcon-unmute command" );
                                                        else
                                                        {

		        	                                if( m_GHost->m_CurrentGame )
	        		                                {
                        			                        for( vector<CGamePlayer *> :: iterator k = m_GHost->m_CurrentGame->m_Players.begin( ); k != m_GHost->m_CurrentGame->m_Players.end( ); ++k )
                	                        		        {
                                	                        		CGamePlayer *Player = m_GHost->m_CurrentGame->GetPlayerFromName( (*k)->GetName( ), true );
		                                        	                if( Player )
                		                                	        {
                                		                        	        if( Player->GetName() == Name )
                                                		                	{
	                                                        		                m_GHost->m_CurrentGame->SendAllChat( "[" + Name + "] got unmuted by [" + User + "] using remote command"   );
        	                                                                		Player->SetMuted( false );
		                	                                                        Success = true;
                		        	                                        }
                                			                        }
                                        			        }
                                        			}
			                                        for( vector<CBaseGame *> :: iterator i = m_GHost->m_Games.begin( ); i != m_GHost->m_Games.end( ); ++i )
        			                                {
                	        		                        for( vector<CGamePlayer *> :: iterator k = (*i)->m_Players.begin( ); k != (*i)->m_Players.end( ); ++k )
                        	                		        {
                                	                        		CGamePlayer *Player = (*i)->GetPlayerFromName( (*k)->GetName( ), true );
		                                        	                if( Player )
                		                                	        {
                                		                        	        if( Player->GetName() == Name )
                                                		                	{
	                                                        		                (*i)->SendAllChat( "[" + Name + "] got unmuted by [" + User + "] using remote command"   );
        	                                                                		Player->SetMuted( false );
		                	                                                        Success = true;
                		        	                                        }
                                			                        }
                                        			        }
                                        			}
							}
						}
/*
	                                        if( Success )
        	                                        QueueChatCommand( "Successfully unmuted [" + Name + "]" );
                	                        else
                        	                        QueueChatCommand( "Could not find [" + Name + "] in any hosted game" );
*/
	                                }

	                                //
        	                        // !KICK
                	                //

	       	                        else if( RCONCommand == "kick" )
        	                        {
              	                        	bool Success = false;
                                        	string User;
	                                        string Name;
        	                                stringstream SS;
                	                        SS << RCONPayload;
                        	                SS >> User;
                                                if( SS.fail( ) )
                                                       CONSOLE_Print( "[BNET: " + m_ServerAlias + "] bad input #1 to rcon-kick command" );
                                                else
                                                {
                                                        SS >> Name;
                                                        if( SS.fail( ) )
                                                               CONSOLE_Print( "[BNET: " + m_ServerAlias + "] bad input #2 to rcon-kick command" );
                                                        else
                                                        {
			                                        if( m_GHost->m_CurrentGame )
        			                                {
                	        		                        for( vector<CGamePlayer *> :: iterator k = m_GHost->m_CurrentGame->m_Players.begin( ); k != m_GHost->m_CurrentGame->m_Players.end( ); ++k )
                        	                		        {
                                	                        		CGamePlayer *Player = m_GHost->m_CurrentGame->GetPlayerFromName( (*k)->GetName( ), true );
		                                        	                if( Player )
                		                                	        {
                                		                        	        if( Player->GetName() == Name )
                                                		                	{
	                                                        		                m_GHost->m_CurrentGame->SendAllChat( "[" + Name + "] got kicked by [" + User + "] using remote command"   );
												Player->SetDeleteMe( true );
												Player->SetLeftReason( m_GHost->m_Language->WasKickedByPlayer( User ) );
												Player->SetLeftCode( PLAYERLEAVE_LOBBY );
												m_GHost->m_CurrentGame->OpenSlot( m_GHost->m_CurrentGame->GetSIDFromPID( Player->GetPID( ) ), false );
												Success = true;
                                	                                		}
		                                        	                }
                		                                	}
	                        		                }
        	                                		for( vector<CBaseGame *> :: iterator i = m_GHost->m_Games.begin( ); i != m_GHost->m_Games.end( ); ++i )
                	                        		{
                        	                        		for( vector<CGamePlayer *> :: iterator k = (*i)->m_Players.begin( ); k != (*i)->m_Players.end( ); ++k )
                                	                		{
		                                        	                CGamePlayer *Player = (*i)->GetPlayerFromName( (*k)->GetName( ), true );
                		                                	        if( Player )
                                		                        	{
	                                        		                        if( Player->GetName() == Name )
        	                                                		        {
                	                                                        		(*i)->SendAllChat( "[" + Name + "] got kicked by [" + User + "] using remote command" );
		                        					                Player->SetDeleteMe( true );
							                                        Player->SetLeftReason( m_GHost->m_Language->WasKickedByPlayer( User ) );
												Player->SetLeftCode( PLAYERLEAVE_LOST );
                                                			                        Success = true;
                                                        			        }
                                                        			}
			                                                }
        			                                }
							}
						}
/*
	                                        if( Success )
        	                                        QueueChatCommand( "Successfully kicked [" + Name + "]" );
                	                        else
                        	                        QueueChatCommand( "Could not find [" + Name + "] in any hosted game" );
*/
	                                }

					//
					// !SAYLOBBY
					//
	                                else if( RCONCommand == "saylobby" )
        	                        {

        	                                bool Success = false;
	                                        string User;
	                                        string Message;
        	                                stringstream SS;
                	                        SS << RCONPayload;
                        	                SS >> User;
                                                if( SS.fail( ) )
                                                       CONSOLE_Print( "[BNET: " + m_ServerAlias + "] bad input #1 to rcon-saylobby command" );
                                                else
                                                {
	                                                if( !SS.eof( ) )
        	                                        {
                	                                        getline( SS, Message );
                        	                                string :: size_type Start = Message.find_first_not_of( " " );

	                                                        if( Start != string :: npos )
        	                                                        Message = Message.substr( Start );
                	                                }

		                                        if( m_GHost->m_CurrentGame && User != "" && Message != "")
							{
	        		                                m_GHost->m_CurrentGame->SendAllChat( "[" + User + "] " + Message );
								Success = true;
							}
						}
/*
	                                                QueueChatCommand( "Successfully send the message );
        	                                else
                	                                QueueChatCommand( "There is no game currently at the lobby" );
*/
	                                }

					//
					// !SAYGAME
					//
        	                        else if( RCONCommand == "saygame" )
	                                {

                                                // extract the game number and the message
                                                // e.g. "3 hello everyone" -> game number: "3", message: "hello everyone"

						string User;
                                                uint32_t GameNumber;
                                                string Message;
                                                stringstream SS;
                                                SS << RCONPayload;
						SS >> User;
                                                if( SS.fail( ) )
                                                       CONSOLE_Print( "[BNET: " + m_ServerAlias + "] bad input #1 to rcon-saygame command" );
                                                else
                                                {
	                                                SS >> GameNumber;
                                                	if( SS.fail( ) )
                                		        	CONSOLE_Print( "[BNET: " + m_ServerAlias + "] bad input #2 to rcon-saygame command" );
                		                        else
		                                        {
		                                                if( !SS.eof( ) )
                		                                {
                                		                        getline( SS, Message );
                                                		        string :: size_type Start = Message.find_first_not_of( " " );

		                                                        if( Start != string :: npos )
                		                                                Message = Message.substr( Start );
                                		                }

		                                                for( vector<CBaseGame *> :: iterator i = m_GHost->m_Games.begin( ); i != m_GHost->m_Games.end( ); ++i )
								{
									if( (*i)->m_HostCounter == GameNumber )
	                		                                	(*i)->SendAllChat( "[" + User + "] " + Message );
								}
                                       			}
						}
					}

					//
					// !LOBBYTEAM
					//
					else if( RCONCommand == "lobbyteam" )
                                        {

                                                bool Success = false;
                                                uint32_t Team;
						string User;
                                                string Message;
                                                stringstream SS;
                                                SS << RCONPayload;
						SS >> User;
                                                if( SS.fail( ) )
                                                       CONSOLE_Print( "[BNET: " + m_ServerAlias + "] bad input #1 to rcon-lobbyteam command" );
                                                else
                                                {
	                                                SS >> Team;
                        	                        if( SS.fail( ) )
                	                                       CONSOLE_Print( "[BNET: " + m_ServerAlias + "] bad input #2 to rcon-lobbyteam command" );
        	                                        else
	                                                {
		                                                if( !SS.eof( ) )
                		                                {
                                		                        getline( SS, Message );
                                                		        string :: size_type Start = Message.find_first_not_of( " " );

		                                                        if( Start != string :: npos )
                		                                                Message = Message.substr( Start );
                                		                }

		                                                if( m_GHost->m_CurrentGame && Message != "" )
                		                                {
                                		                        for( vector<CGamePlayer *> :: iterator k = m_GHost->m_CurrentGame->m_Players.begin( ); k != m_GHost->m_CurrentGame->m_Players.end( ); ++k )
                                                		        {
                                        		        		unsigned char SID = m_GHost->m_CurrentGame->GetSIDFromPID( (*k)->GetPID() );
		                                		                string slot = UTIL_ToString( m_GHost->m_CurrentGame->GetSIDFromPID( (*k)->GetPID() ) );
                		        		                        unsigned char fteam;
                				                                fteam = m_GHost->m_CurrentGame->m_Slots[SID].GetTeam();
                                                		                if( fteam == Team )
                                                                		{
	        	                                                        	m_GHost->m_CurrentGame->SendChat( (*k), "[TC:"+ User +"] "+ Message );
        	        	                                                        Success = true;
                                                		                }
                                		                        }
                                              			}
								else
									CONSOLE_Print( "There is no game at the lobby" );
							}
						}
					}

					//
					// !GAMETEAM
					//
                                        else if( RCONCommand == "gameteam" )
                                        {

                                                bool Success = false;
                                                uint32_t Team;
						uint32_t GameNumber;
                                                string User;
                                                string Message;
                                                stringstream SS;
                                                SS << RCONPayload;
                                                SS >> User;
                                                if( SS.fail( ) )
                                                       CONSOLE_Print( "[BNET: " + m_ServerAlias + "] bad input #1 to rcon-gameteam command" );
                                                else
                                                {
							SS >> GameNumber;
                        	                        if( SS.fail( ) )
                	                                       CONSOLE_Print( "[BNET: " + m_ServerAlias + "] bad input #2 to rcon-gameteam command" );
        	                                        else
	                                                {
		                                                SS >> Team;
                                                		if( SS.fail( ) )
                                		                       CONSOLE_Print( "[BNET: " + m_ServerAlias + "] bad input #3 to rcon-gameteam command" );
                		                                else
		                                                {
			                                                if( !SS.eof( ) )
                        			                        {
                                                			        getline( SS, Message );
                                                        			string :: size_type Start = Message.find_first_not_of( " " );

			                                                        if( Start != string :: npos )
                        			                                        Message = Message.substr( Start );
                                                			}
	                                                                for( vector<CBaseGame *> :: iterator i = m_GHost->m_Games.begin( ); i != m_GHost->m_Games.end( ); ++i )
        	                                                        {
                	                                                        if( (*i)->m_HostCounter == GameNumber )
										{
	                                                			        for( vector<CGamePlayer *> :: iterator k = m_GHost->m_Games[GameNumber - 1]->m_Players.begin( ); k != m_GHost->m_Games[GameNumber - 1]->m_Players.end( ); ++k )
        	                                                			{
				                                                                unsigned char SID = m_GHost->m_Games[GameNumber - 1]->GetSIDFromPID( (*k)->GetPID() );
                        				                                        string slot = UTIL_ToString( m_GHost->m_Games[GameNumber - 1]->GetSIDFromPID( (*k)->GetPID() ) );
                                	                			                unsigned char fteam;
                                        	                        			fteam = m_GHost->m_Games[GameNumber - 1]->m_Slots[SID].GetTeam();
                                                	                			if( fteam == Team )
                                                        	        			{
			                                        	                                m_GHost->m_Games[GameNumber - 1]->SendChat( (*k), "[TC:"+ User +"] "+ Message );
                        			                        	                        Success = true;
                                                			        	        }
											}
                                                        			}
                                                			}
								}

							}

						}
					}

					//
					// RCON FROM
					//
                        		else if( RCONCommand == "from" && m_GHost->m_CurrentGame )
                        		{
                                		string Froms;

                                		for( vector<CGamePlayer *> :: iterator i = m_GHost->m_CurrentGame->m_Players.begin( ); i != m_GHost->m_CurrentGame->m_Players.end( ); ++i )
                                		{
                                       			// we reverse the byte order on the IP because it's stored in network byte order

                                        		Froms += (*i)->GetNameTerminated( );
                                		        Froms += ": (";
                		                        Froms += (*i)->GetCLetter( );
		                                        Froms += ")";

                                        		if( i != m_GHost->m_CurrentGame->m_Players.end( ) - 1 )
                                        		        Froms += ", ";

                                		        if( ( m_GHost->m_CurrentGame->m_GameLoading || m_GHost->m_CurrentGame->m_GameLoaded ) && Froms.size( ) > 100 )
                        		                {
                        		                        // cut the text into multiple lines ingame

                        		                        m_GHost->m_CurrentGame->SendAllChat( Froms );
                		                                Froms.clear( );
        		                                }
		                                }

                		                if( !Froms.empty( ) )
        		                                m_GHost->m_CurrentGame->SendAllChat( Froms );
		                        }

				}

				//
				// !VOUCH
				//
				if( Command == "vouch" && IsLevel( User ) >= 6 && !Payload.empty() )
				{
					string Name;
					string Realm;
					stringstream SS;
					SS << Payload;
					SS >> Name;
                                        if( Name.length() <= 3 )
                                        {
                                                QueueChatCommand( "This is not a valied Name" );
                                                return;
                                        }
                                        SS >> Realm;
                                        if( SS.fail( ) || Realm.empty() )
	                                        Realm = m_Server;

					m_PairedPUps.push_back( PairedPUp( Whisper ? User : string( ), m_GHost->m_DB->ThreadedPUp( Name, 1, Realm, User ) ) );
				}

				//
				// !SETPERMISSION
				//
				if( ( Command == "setp" || Command == "sep" || Command == "setpermission" ) && IsLevel( User ) == 10 )
				{
					string Name;
					string NewLevel;
					string Realm = "";
					stringstream SS;
					SS << Payload;
					SS >> Name;

					if( Name.length() <= 3 )
					{
						QueueChatCommand( "This is not a valied Name" );
						return;
					}

                                        SS >> NewLevel;

                                        if( SS.fail( ) || NewLevel.empty() )
                                       	{
						QueueChatCommand( "Error. Wrong input, please add a level." );
						return;
					}
					else
					{
						if( !NewLevel.find_first_not_of( "1234567890" ) == string :: npos )
						{
							QueueChatCommand( "This is not a valied Level please use a correct number" );
							return;
						}
						SS >> Realm;
						if( SS.fail( ) || Realm.empty() )
							Realm = m_Server;

						m_PairedPUps.push_back( PairedPUp( Whisper ? User : string( ), m_GHost->m_DB->ThreadedPUp( Name, UTIL_ToUInt32( NewLevel ), Realm, User ) ) );
					}
				}

				//
				// !UPDATE ALL STATS
				//
				else if ( Command == "update" && IsLevel( User ) == 10 )
				{
#ifdef WIN32
					system("stats.exe");
#else
					system("./stats");
#endif
				}

				//
				// !PERMISSION
				//
				else if ( ( Command == "perm" || Command == "permission" ) && IsLevel( User ) == 10 )
				{
					string StatsUser = User;
					if( !Payload.empty() )
						StatsUser = Payload;

					QueueChatCommand( "User ["+StatsUser+"] is currently on Level: "+UTIL_ToString( IsLevel( StatsUser ) ) );
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
                                                QueueChatCommand( "Error. The name is to short, please add a valied name" );
                                        else if( IsLevel( User ) != 10 && ( ( IsLevel( User ) == 9 && IsLevel( Victim ) >= 6 ) || ( ( IsLevel( User ) == 5 || IsLevel( User ) == 6 || IsLevel( User ) == 7 ) && IsLevel( Victim ) >= 2 ) ) )
                                                QueueChatCommand( "Error. You have no access to punish this player.", User, Whisper );
                                        else
                                        {
                                                SS >> Amount;

                                                if( SS.fail( ) || Amount == "0" )
                                                        CONSOLE_Print( "[PP] bad input #2 to !TEMPBAN command" );
						else if( ( UTIL_ToUInt32( Amount ) > 3 && IsLevel( User ) < 8 ) || UTIL_ToUInt32( Amount ) > 10 && IsLevel( User ) <= 10 )
							QueueChatCommand( "You shouldn't add more than 3 penality points" );
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
								m_Pairedpenps.push_back( Pairedpenp( string(), m_GHost->m_DB->Threadedpenp( Victim, Reason, User, UTIL_ToUInt32( Amount ), "add" ) ) );
							}
							else
								QueueChatCommand( "Error. Please add a reason to punish someone" );
						}
					}
				}

                                //
                                // !RPP      !REMOVEPP
                                //
                                else if( ( Command == "rpp" || Command == "removepp" ) && IsLevel( User ) >= 9 )
                                {
                                        string SUser;
					string Amount;
					string Reason;
					stringstream SS;
					SS << Payload;
					SS >> SUser;
					if( SS.fail( ) || SUser.empty() )
						QueueChatCommand( "Error, bad input.", User, Whisper );
					else
					{
						SS >> Amount;
						if( SS.fail( ) || Amount.empty() )
							QueueChatCommand( "Error, bad input.", User, Whisper );
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
								m_PairedSSs.push_back( PairedSS( Whisper ? User : string( ), m_GHost->m_DB->ThreadedStatsSystem( SUser, Reason, UTIL_ToUInt32(Amount), "rpp" ) ) );
                                        		else
								m_PairedSSs.push_back( PairedSS( Whisper ? User : string( ), m_GHost->m_DB->ThreadedStatsSystem( SUser, "", UTIL_ToUInt32(Amount), "rpp" ) ) ) ;
						}
					}
                                }

				else if( Command == "tosu" )
				{
					b_StatsUpdate = true;
				}

                                /*****************
                                * ADMIN COMMANDS *
                                *****************/

				//
				// !TEST
				//
				else if( Command == "test" )
				{
	                                for( vector<string> :: iterator i = m_GHost->m_ColoredNames.begin( ); i != m_GHost->m_ColoredNames.end( ); )
        	                        {
						CONSOLE_Print( "Found colored name: "+ *i );
						i++;
					}
                                        for( vector<string> :: iterator i = m_GHost->m_Modes.begin( ); i != m_GHost->m_Modes.end( ); )
                                        {
						CONSOLE_Print( "Found a mode: "+ *i );
                                                i++;
                                        }
                                }

				//
				// !ADDBAN
				// !BAN
				//

				else if( ( Command == "addban" || Command == "ban" ) && !Payload.empty( ) )
				{
                                        if( IsLevel( User ) >= 7 )
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
						if( !Reason.empty() )
						{
							//if( IsBannedName( Victim ) )
							//	QueueChatCommand( m_GHost->m_Language->UserIsAlreadyBanned( m_Server, Victim ), User, Whisper );
							//else
								m_PairedBanAdds.push_back( PairedBanAdd( Whisper ? User : string( ), m_GHost->m_DB->ThreadedBanAdd( m_Server, Victim, string( ), string( ), User, Reason, 0, "" ) ) );
						}
						else
							QueueChatCommand( "Error. Please add a reason.", User, Whisper );
                                        }
                                        else
                                                QueueChatCommand( m_GHost->m_Language->YouDontHaveAccessToThatCommand( ), User, Whisper );
				}

                                //
                                // !IRB
                                // !IPRANGEADD
                                // !IPRANGEBAN
                                // PLEASE USE THIS BAN WITH CAUTION, IF YOU ARE NOT SURE WITH SUBNET's OR SIMILAIR THINGS, DON'T USE IT!
				//
                                else if( ( Command == "irb" || Command == "iprangeadd"  || Command == "iprangeban" ) && !Payload.empty( ) && IsLevel( User ) == 10 )
                                {
                                        string VictimIP;
                                        string Reason;
                                        stringstream SS;
                                        SS << Payload;
                                        SS >> VictimIP;

                                        if( !SS.eof( ) )
                                        {
                                                getline( SS, Reason );
                                                string :: size_type Start = Reason.find_first_not_of( " " );

                                                if( Start != string :: npos )
                                                        Reason = Reason.substr( Start );
                                        }

                                        if( IsBannedName( VictimIP ) )
                                                QueueChatCommand( m_GHost->m_Language->UserIsAlreadyBanned( m_Server, VictimIP ), User, Whisper );
                                        else
					{
                                                m_PairedBanAdds.push_back( PairedBanAdd( Whisper ? User : string( ), m_GHost->m_DB->ThreadedBanAdd( m_Server, "IPRange", ":" + VictimIP, string( ), User, Reason, 0, "" ) ) );
                                                QueueChatCommand( "Banned IPRange " + VictimIP + " on " + m_Server + ".", User, Whisper );
                                        }
                                }

				//
				// !TEMPBAN
				// !TBAN
				//
				else if( ( Command == "tempban" || Command == "tban" ) && !Payload.empty( ) )
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
						QueueChatCommand( "Error. The name is to short, please add a valied name" );
					else if( IsLevel( User ) != 10 && ( ( IsLevel( User ) == 9 && IsLevel( Victim ) >= 6 ) || ( ( IsLevel( User ) == 5 || IsLevel( User ) == 6 || IsLevel( User ) == 7 ) && IsLevel( Victim ) >= 2 ) ) )
						QueueChatCommand( "Error. You have no access to ban this player.", User, Whisper );
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
								transform( Suffix.begin( ), Suffix.end( ), Suffix.begin( ), (int(*)(int))tolower );

								// handle suffix
								// valid suffix is: hour, h, week, w, day, d, month, m

								bool ValidSuffix = false;
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
									if( ( IsLevel( User ) == 5 ||  IsLevel( User ) == 6 ) && ( ( Suffix == "hour" || Suffix == "hours" || Suffix == "h" ) || ( ( Suffix == "days" || Suffix == "d" || Suffix == "day" ) && Amount <= 5 ) ) || IsLevel( User ) >= 7 )
									{
										if (!SS.eof())
										{
											getline( SS, Reason );
											string :: size_type Start = Reason.find_first_not_of( " " );

											if( Start != string :: npos )
												Reason = Reason.substr( Start );
										}

										if( IsBannedName( Victim ) )
											QueueChatCommand( m_GHost->m_Language->UserIsAlreadyBanned( m_Server, Victim ), User, Whisper );
										else
										{
											if( !Reason.empty() )
											{
												m_PairedBanAdds.push_back( PairedBanAdd( Whisper ? User : string( ), m_GHost->m_DB->ThreadedBanAdd( m_Server, Victim, string( ), string( ), User, Reason, BanTime, "" ) ) );
												//QueueChatCommand( "Temporary ban: " + Victim + " for " + UTIL_ToString(Amount) + " " + Suffix + " with reason: " + Reason, User, Whisper);
											}
											else
												QueueChatCommand( "Error. Please add a reason", User, Whisper );
										}
									}
									else
										QueueChatCommand( "Error. You have no access to ban a user more than 5 days", User, Whisper );

								}
								else
								{
									QueueChatCommand("Bad input, expected minute(s)/hour(s)/day(s)/week(s)/month(s), you said: " + Suffix, User, Whisper);
								}
							}
						}
					}
				}

				//
				// !ANNOUNCE
				//

				else if( Command == "announce" && m_GHost->m_CurrentGame && !m_GHost->m_CurrentGame->GetCountDownStarted( ) )
				{
					if( Payload.empty( ) || Payload == "off" )
					{
						QueueChatCommand( m_GHost->m_Language->AnnounceMessageDisabled( ), User, Whisper );
						m_GHost->m_CurrentGame->SetAnnounce( 0, string( ) );
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
							CONSOLE_Print( "[BNET: " + m_ServerAlias + "] bad input #1 to announce command" );
						else
						{
							if( SS.eof( ) )
								CONSOLE_Print( "[BNET: " + m_ServerAlias + "] missing input #2 to announce command" );
							else
							{
								getline( SS, Message );
								string :: size_type Start = Message.find_first_not_of( " " );

								if( Start != string :: npos )
									Message = Message.substr( Start );

								QueueChatCommand( m_GHost->m_Language->AnnounceMessageEnabled( ), User, Whisper );
								m_GHost->m_CurrentGame->SetAnnounce( Interval, Message );
							}
						}
					}
				}

				//
				// !AUTOHOST
				//

				else if( Command == "autohost" )
				{
					if( IsLevel( User ) == 10 || ForceRoot )
					{
						if( Payload.empty( ) || Payload == "off" )
						{
							QueueChatCommand( m_GHost->m_Language->AutoHostDisabled( ), User, Whisper );
							m_GHost->m_AutoHostGameName.clear( );
							m_GHost->m_AutoHostOwner.clear( );
							m_GHost->m_AutoHostServer.clear( );
							m_GHost->m_AutoHostMaximumGames = 0;
							m_GHost->m_AutoHostAutoStartPlayers = 0;
							m_GHost->m_AutoHostGameType = 3;
							m_GHost->m_LastAutoHostTime = GetTime( );
							m_GHost->m_AutoHostMatchMaking = false;
							m_GHost->m_AutoHostMinimumScore = 0.0;
							m_GHost->m_AutoHostMaximumScore = 0.0;
						}
						else
						{
							// extract the maximum games, auto start players, and the game name
							// e.g. "5 10 BattleShips Pro" -> maximum games: "5", auto start players: "10", game name: "BattleShips Pro"

							uint32_t MaximumGames;
							uint32_t AutoStartPlayers;
							uint32_t GameType;
							string GameName;
							stringstream SS;
							SS << Payload;
							SS >> MaximumGames;

							if( SS.fail( ) || MaximumGames == 0 )
								CONSOLE_Print( "[BNET: " + m_ServerAlias + "] bad input #1 to autohost command" );
							else
							{
								SS >> AutoStartPlayers;

								if( SS.fail( ) || AutoStartPlayers == 0 )
									CONSOLE_Print( "[BNET: " + m_ServerAlias + "] bad input #2 to autohost command" );
								else
								{
									SS >> GameType;
                        	                                        if( SS.fail( ) || GameType < 3 || GameType > 5 )
                	                                                        CONSOLE_Print( "[BNET: " + m_ServerAlias + "] bad input #3 to autohost command" );
        	                                                        else
	                                                                {
										if( SS.eof( ) )
											CONSOLE_Print( "[BNET: " + m_ServerAlias + "] missing input #4 to autohost command" );
										else
										{
											getline( SS, GameName );
											string :: size_type Start = GameName.find_first_not_of( " " );

											if( Start != string :: npos )
												GameName = GameName.substr( Start );

											QueueChatCommand( m_GHost->m_Language->AutoHostEnabled( ), User, Whisper );
											delete m_GHost->m_AutoHostMap;
											m_GHost->m_AutoHostMap = new CMap( *m_GHost->m_Map );
											if( GameType == 4 )
												m_GHost->m_AutoHostGameName = "[V]"+GameName;
											else if( GameType == 5 )
                                                                                                m_GHost->m_AutoHostGameName = "[R]"+GameName;
                                                                                        else
                                                                                                m_GHost->m_AutoHostGameName = GameName;
											m_GHost->m_AutoHostOwner = User;
											m_GHost->m_AutoHostServer = m_Server;
											m_GHost->m_AutoHostGameType = GameType;
											m_GHost->m_AutoHostMaximumGames = MaximumGames;
											m_GHost->m_AutoHostAutoStartPlayers = AutoStartPlayers;
											m_GHost->m_LastAutoHostTime = GetTime( );
											m_GHost->m_AutoHostMatchMaking = false;
											m_GHost->m_AutoHostMinimumScore = 0.0;
											m_GHost->m_AutoHostMaximumScore = 0.0;
										}
									}
								}
							}
						}
					}
					else
						QueueChatCommand( m_GHost->m_Language->YouDontHaveAccessToThatCommand( ), User, Whisper );
				}

				//
				// !AUTOHOSTMM
				//

				else if( Command == "autohostmm" )
				{
					if( IsLevel( User ) == 10 || ForceRoot )
					{
						if( Payload.empty( ) || Payload == "off" )
						{
							QueueChatCommand( m_GHost->m_Language->AutoHostDisabled( ), User, Whisper );
							m_GHost->m_AutoHostGameName.clear( );
							m_GHost->m_AutoHostOwner.clear( );
							m_GHost->m_AutoHostServer.clear( );
							m_GHost->m_AutoHostMaximumGames = 0;
							m_GHost->m_AutoHostAutoStartPlayers = 0;
							m_GHost->m_LastAutoHostTime = GetTime( );
							m_GHost->m_AutoHostMatchMaking = false;
							m_GHost->m_AutoHostMinimumScore = 0.0;
							m_GHost->m_AutoHostMaximumScore = 0.0;
                                                        m_GHost->m_AutoHostGameType = 3;
						}
						else
						{
							// extract the maximum games, auto start players, minimum score, maximum score, and the game name
							// e.g. "5 10 800 1200 BattleShips Pro" -> maximum games: "5", auto start players: "10", minimum score: "800", maximum score: "1200", game name: "BattleShips Pro"

							uint32_t MaximumGames;
							uint32_t AutoStartPlayers;
							uint32_t GameType;
							double MinimumScore;
							double MaximumScore;
							string GameName;
							stringstream SS;
							SS << Payload;
							SS >> MaximumGames;

							if( SS.fail( ) || MaximumGames == 0 )
								CONSOLE_Print( "[BNET: " + m_ServerAlias + "] bad input #1 to autohostmm command" );
							else
							{
								SS >> AutoStartPlayers;

								if( SS.fail( ) || AutoStartPlayers == 0 )
									CONSOLE_Print( "[BNET: " + m_ServerAlias + "] bad input #2 to autohostmm command" );
								else
								{
									SS >> GameType;
                	                                                if( SS.fail( ) || AutoStartPlayers == 0 )
                        	                                                CONSOLE_Print( "[BNET: " + m_ServerAlias + "] bad input #2 to autohostmm command" );
        	                                                        else
	                                                                {
										SS >> MinimumScore;

										if( SS.fail( ) )
											CONSOLE_Print( "[BNET: " + m_ServerAlias + "] bad input #3 to autohostmm command" );
										else
										{
											SS >> MaximumScore;

											if( SS.fail( ) )
												CONSOLE_Print( "[BNET: " + m_ServerAlias + "] bad input #4 to autohostmm command" );
											else
											{
												if( SS.eof( ) )
													CONSOLE_Print( "[BNET: " + m_ServerAlias + "] missing input #5 to autohostmm command" );
												else
												{
													getline( SS, GameName );
													string :: size_type Start = GameName.find_first_not_of( " " );

													if( Start != string :: npos )
														GameName = GameName.substr( Start );

													QueueChatCommand( m_GHost->m_Language->AutoHostEnabled( ), User, Whisper );
													delete m_GHost->m_AutoHostMap;
													m_GHost->m_AutoHostMap = new CMap( *m_GHost->m_Map );
													m_GHost->m_AutoHostGameName = GameName;
													m_GHost->m_AutoHostOwner = User;
													m_GHost->m_AutoHostServer = m_Server;
													m_GHost->m_AutoHostMaximumGames = MaximumGames;
													m_GHost->m_AutoHostAutoStartPlayers = AutoStartPlayers;
													m_GHost->m_LastAutoHostTime = GetTime( );
													m_GHost->m_AutoHostMatchMaking = true;
													m_GHost->m_AutoHostMinimumScore = MinimumScore;
													m_GHost->m_AutoHostMaximumScore = MaximumScore;
		                                                                                        m_GHost->m_AutoHostGameType = GameType;
												}
											}
										}
									}
								}
							}
						}
					}
					else
						QueueChatCommand( m_GHost->m_Language->YouDontHaveAccessToThatCommand( ), User, Whisper );
				}

				//
				// !AUTOSTART
				//

				else if( Command == "autostart" && m_GHost->m_CurrentGame && !m_GHost->m_CurrentGame->GetCountDownStarted( ) && IsLevel( User ) >= 9 )
				{
					if( Payload.empty( ) || Payload == "off" )
					{
						QueueChatCommand( m_GHost->m_Language->AutoStartDisabled( ), User, Whisper );
						m_GHost->m_CurrentGame->SetAutoStartPlayers( 0 );
					}
					else
					{
						uint32_t AutoStartPlayers = UTIL_ToUInt32( Payload );

						if( AutoStartPlayers != 0 )
						{
							QueueChatCommand( m_GHost->m_Language->AutoStartEnabled( UTIL_ToString( AutoStartPlayers ) ), User, Whisper );
							m_GHost->m_CurrentGame->SetAutoStartPlayers( AutoStartPlayers );
						}
					}
				}

				//
				// !CHANNEL (change channel)
				//

				else if( Command == "channel" && !Payload.empty( ) && IsLevel( User ) >= 9 )
					QueueChatCommand( "/join " + Payload );

				//
				// !CHECKBAN
				//

				else if( Command == "checkban" && !Payload.empty( ) )
				{
					CDBBan *Ban = IsBannedName( Payload );

					if( Ban )
					{
						string Remaining;
                                                if( Ban->GetMonths() != "" )
                                                        Remaining += Ban->GetMonths() + "w ";
						if( Ban->GetDays() != "" )
							Remaining += Ban->GetDays() + "d ";
                                                if( Ban->GetHours() != "" )
                                                        Remaining += Ban->GetHours() + "h ";
                                                if( Ban->GetMinutes() != "" )
                                                        Remaining += Ban->GetMinutes() + "m";

						if( !Remaining.empty() )
							QueueChatCommand( "User [" + Ban->GetName() + "] banned on [" + Ban->GetDate() + "] till [" + Ban->GetExpire() + "] (Remaining: " + Remaining + ") Reason: " + Ban->GetReason() );
						else
							QueueChatCommand( "User [" + Ban->GetName() + "] permanently banned on [" + Ban->GetDate() + "] for [" + Ban->GetReason() + "]", User, Whisper );
					}
					else
						QueueChatCommand( m_GHost->m_Language->UserIsNotBanned( m_Server, Payload ), User, Whisper );
				}

                                //
                                // !CHECKIPBAN
                                //

                                else if( Command == "checkipban" && !Payload.empty( ) )
                                {
                                        CDBBan *Ban = IsBannedName( Payload );
                                        if( Ban )
                                        {
                                                QueueChatCommand( "Your Name got banned. Check the details with 'checkban'", User, Whisper );
                                                return;
                                        }
                                        else
                                        {
                                                m_PairedBanCheck2s.push_back( PairedBanCheck2( Whisper ? User : string( ), m_GHost->m_DB->ThreadedBanCheck2( m_Server, Payload, "check" ) ) );
                                        }
                                }

				//
				// !CLOSE (close slot)
				//

				else if( Command == "close" && !Payload.empty( ) && m_GHost->m_CurrentGame && IsLevel( User ) >= 7 )
				{
					if( !m_GHost->m_CurrentGame->GetLocked( ) )
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
								CONSOLE_Print( "[BNET: " + m_ServerAlias + "] bad input to close command" );
								break;
							}
							else
								m_GHost->m_CurrentGame->CloseSlot( (unsigned char)( SID - 1 ), true );
						}
					}
					else
						QueueChatCommand( m_GHost->m_Language->TheGameIsLockedBNET( ), User, Whisper );
				}

				//
				// !CLOSEALL
				//

				else if( Command == "closeall" && m_GHost->m_CurrentGame && IsLevel( User ) >= 9 )
				{
					if( !m_GHost->m_CurrentGame->GetLocked( ) )
						m_GHost->m_CurrentGame->CloseAllSlots( );
					else
						QueueChatCommand( m_GHost->m_Language->TheGameIsLockedBNET( ), User, Whisper );
				}

				//
				// !COUNTBANS
				//

				else if( Command == "countbans" && IsLevel( User ) >= 9 )
					m_PairedBanCounts.push_back( PairedBanCount( Whisper ? User : string( ), m_GHost->m_DB->ThreadedBanCount( m_Server ) ) );

				//
				// !DBSTATUS
				//

				else if( Command == "dbstatus" && IsLevel( User ) >= 9 )
					QueueChatCommand( m_GHost->m_DB->GetStatus( ), User, Whisper );

				//
				// !DELBAN
				// !UNBAN
				//

				else if( ( Command == "delban" || Command == "unban" ) && !Payload.empty( ) && IsLevel( User ) >= 7 )
					m_PairedBanRemoves.push_back( PairedBanRemove( Whisper ? User : string( ), m_GHost->m_DB->ThreadedBanRemove( Payload ) ) );

				//
				// !DISABLE
				//

				else if( Command == "disable" )
				{
					if( IsLevel( User ) == 10 || ForceRoot )
					{
						QueueChatCommand( m_GHost->m_Language->BotDisabled( ), User, Whisper );
						m_GHost->m_Enabled = false;
					}
					else
						QueueChatCommand( m_GHost->m_Language->YouDontHaveAccessToThatCommand( ), User, Whisper );
				}

				//
				// !DOWNLOADS
				//

				else if( Command == "downloads" && !Payload.empty( ) )
				{
					uint32_t Downloads = UTIL_ToUInt32( Payload );

					if( Downloads == 0 )
					{
						QueueChatCommand( m_GHost->m_Language->MapDownloadsDisabled( ), User, Whisper );
						m_GHost->m_AllowDownloads = 0;
					}
					else if( Downloads == 1 )
					{
						QueueChatCommand( m_GHost->m_Language->MapDownloadsEnabled( ), User, Whisper );
						m_GHost->m_AllowDownloads = 1;
					}
					else if( Downloads == 2 )
					{
						QueueChatCommand( m_GHost->m_Language->MapDownloadsConditional( ), User, Whisper );
						m_GHost->m_AllowDownloads = 2;
					}
				}

				//
				// !ENABLE
				//

				else if( Command == "enable" )
				{
					if( IsLevel( User ) == 10 || ForceRoot )
					{
						QueueChatCommand( m_GHost->m_Language->BotEnabled( ), User, Whisper );
						m_GHost->m_Enabled = true;
					}
					else
						QueueChatCommand( m_GHost->m_Language->YouDontHaveAccessToThatCommand( ), User, Whisper );
				}

				//
				// !END
				//

				else if( Command == "end" && !Payload.empty( ) && IsLevel( User ) >= 7 )
				{
					// todotodo: what if a game ends just as you're typing this command and the numbering changes?

					uint32_t GameNumber = UTIL_ToUInt32( Payload ) - 1;

					if( GameNumber < m_GHost->m_Games.size( ) )
					{
						// if the game owner is still in the game only allow the root admin to end the game

						if( m_GHost->m_Games[GameNumber]->GetPlayerFromName( m_GHost->m_Games[GameNumber]->GetOwnerName( ), false ) && IsLevel( User ) != 10 )
							QueueChatCommand( m_GHost->m_Language->CantEndGameOwnerIsStillPlaying( m_GHost->m_Games[GameNumber]->GetOwnerName( ) ), User, Whisper );
						else
						{
							QueueChatCommand( m_GHost->m_Language->EndingGame( m_GHost->m_Games[GameNumber]->GetDescription( ) ), User, Whisper );
							CONSOLE_Print( "[GAME: " + m_GHost->m_Games[GameNumber]->GetGameName( ) + "] is over (admin ended game)" );
							m_GHost->m_Games[GameNumber]->StopPlayers( "was disconnected (admin ended game)" );
						}
					}
					else
						QueueChatCommand( m_GHost->m_Language->GameNumberDoesntExist( Payload ), User, Whisper );
				}

				//
				// !ENFORCESG
				//

				else if( Command == "enforcesg" && !Payload.empty( ) )
				{
					// only load files in the current directory just to be safe

					if( Payload.find( "/" ) != string :: npos || Payload.find( "\\" ) != string :: npos )
						QueueChatCommand( m_GHost->m_Language->UnableToLoadReplaysOutside( ), User, Whisper );
					else
					{
						string File = m_GHost->m_ReplayPath + Payload + ".w3g";

						if( UTIL_FileExists( File ) )
						{
							QueueChatCommand( m_GHost->m_Language->LoadingReplay( File ), User, Whisper );
							CReplay *Replay = new CReplay( );
							Replay->Load( File, false );
							Replay->ParseReplay( false );
							m_GHost->m_EnforcePlayers = Replay->GetPlayers( );
							delete Replay;
						}
						else
							QueueChatCommand( m_GHost->m_Language->UnableToLoadReplayDoesntExist( File ), User, Whisper );
					}
				}

				//
				// !EXIT
				// !QUIT
				//

				else if( Command == "exit" || Command == "quit" )
				{
					if( IsLevel( User ) == 10 || ForceRoot )
					{
						if( Payload == "nice" )
							m_GHost->m_ExitingNice = true;
						else if( Payload == "force" )
							m_Exiting = true;
						else
						{
							if( m_GHost->m_CurrentGame || !m_GHost->m_Games.empty( ) )
								QueueChatCommand( m_GHost->m_Language->AtLeastOneGameActiveUseForceToShutdown( ), User, Whisper );
							else
								m_Exiting = true;
						}
					}
					else
						QueueChatCommand( m_GHost->m_Language->YouDontHaveAccessToThatCommand( ), User, Whisper );
				}

				//
				// !GETCLAN
				//

				else if( Command == "getclan" )
				{
					SendGetClanList( );
					QueueChatCommand( m_GHost->m_Language->UpdatingClanList( ), User, Whisper );
				}

				//
				// !GETFRIENDS
				//

				else if( Command == "getfriends" )
				{
					SendGetFriendsList( );
					QueueChatCommand( m_GHost->m_Language->UpdatingFriendsList( ), User, Whisper );
				}

				//
				// !GETGAME
				//

				else if( Command == "getgame" && !Payload.empty( ) && IsLevel( User ) >= 9 )
				{
					uint32_t GameNumber = UTIL_ToUInt32( Payload ) - 1;

					if( GameNumber < m_GHost->m_Games.size( ) )
						QueueChatCommand( m_GHost->m_Language->GameNumberIs( Payload, m_GHost->m_Games[GameNumber]->GetDescription( ) ), User, Whisper );
					else
						QueueChatCommand( m_GHost->m_Language->GameNumberDoesntExist( Payload ), User, Whisper );
				}

				//
				// !GETGAMES
				//

				else if( Command == "getgames" && IsLevel( User ) >= 9 )
				{
					if( m_GHost->m_CurrentGame )
						QueueChatCommand( m_GHost->m_Language->GameIsInTheLobby( m_GHost->m_CurrentGame->GetDescription( ), UTIL_ToString( m_GHost->m_Games.size( ) ), UTIL_ToString( m_GHost->m_MaxGames ) ), User, Whisper );
					else
						QueueChatCommand( m_GHost->m_Language->ThereIsNoGameInTheLobby( UTIL_ToString( m_GHost->m_Games.size( ) ), UTIL_ToString( m_GHost->m_MaxGames ) ), User, Whisper );
				}

				//
				// !HOLD (hold a slot for someone)
				//

				else if( Command == "hold" && !Payload.empty( ) && m_GHost->m_CurrentGame && IsLevel( User ) >= 8 )
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
							CONSOLE_Print( "[BNET: " + m_ServerAlias + "] bad input to hold command" );
							break;
						}
						else
						{
							QueueChatCommand( m_GHost->m_Language->AddedPlayerToTheHoldList( HoldName ), User, Whisper );
							m_GHost->m_CurrentGame->AddToReserved( HoldName );
						}
					}
				}

				//
				// !HOSTSG
				//

				else if( Command == "hostsg" && !Payload.empty( ) && IsLevel( User ) >= 8 )
					m_GHost->CreateGame( m_GHost->m_Map, GAME_PRIVATE, true, Payload, User, User, m_Server, 1, Whisper );

				//
				// !LOAD (load config file)
				//

				else if( Command == "load" && IsLevel( User ) >= 8 )
				{
					if( Payload.empty( ) )
						QueueChatCommand( m_GHost->m_Language->CurrentlyLoadedMapCFGIs( m_GHost->m_Map->GetCFGFile( ) ), User, Whisper );
					else
					{
						string FoundMapConfigs;

						try
						{
							path MapCFGPath( m_GHost->m_MapCFGPath );
							string Pattern = Payload;
							transform( Pattern.begin( ), Pattern.end( ), Pattern.begin( ), (int(*)(int))tolower );

							if( !exists( MapCFGPath ) )
							{
								CONSOLE_Print( "[BNET: " + m_ServerAlias + "] error listing map configs - map config path doesn't exist" );
								QueueChatCommand( m_GHost->m_Language->ErrorListingMapConfigs( ), User, Whisper );
							}
							else
							{
								directory_iterator EndIterator;
								path LastMatch;
								uint32_t Matches = 0;

								for( directory_iterator i( MapCFGPath ); i != EndIterator; ++i )
								{
									string FileName = i->path( ).filename( ).string( );
									string Stem = i->path( ).stem( ).string( );
									transform( FileName.begin( ), FileName.end( ), FileName.begin( ), (int(*)(int))tolower );
									transform( Stem.begin( ), Stem.end( ), Stem.begin( ), (int(*)(int))tolower );

									if( !is_directory( i->status( ) ) && i->path( ).extension( ) == ".cfg" && FileName.find( Pattern ) != string :: npos )
									{
										LastMatch = i->path( );
										++Matches;

										if( FoundMapConfigs.empty( ) )
											FoundMapConfigs = i->path( ).filename( ).string( );
										else
											FoundMapConfigs += ", " + i->path( ).filename( ).string( );

										// if the pattern matches the filename exactly, with or without extension, stop any further matching

										if( FileName == Pattern || Stem == Pattern )
										{
											Matches = 1;
											break;
										}
									}
								}

								if( Matches == 0 )
									QueueChatCommand( m_GHost->m_Language->NoMapConfigsFound( ), User, Whisper );
								else if( Matches == 1 )
								{
									string File = LastMatch.filename( ).string( );
									QueueChatCommand( m_GHost->m_Language->LoadingConfigFile( m_GHost->m_MapCFGPath + File ), User, Whisper );
									CConfig MapCFG;
									MapCFG.Read( LastMatch.string( ) );
									m_GHost->m_Map->Load( &MapCFG, m_GHost->m_MapCFGPath + File );
								}
								else
									QueueChatCommand( m_GHost->m_Language->FoundMapConfigs( FoundMapConfigs ), User, Whisper );
							}
						}
						catch( const exception &ex )
						{
							CONSOLE_Print( "[BNET: " + m_ServerAlias + "] error listing map configs - caught exception [" + ex.what( ) + "]" );
							QueueChatCommand( m_GHost->m_Language->ErrorListingMapConfigs( ), User, Whisper );
						}
					}
				}

				//
				// !LOADSG
				//

				else if( Command == "loadsg" && !Payload.empty( ) && IsLevel( User ) >= 8 )
				{
					// only load files in the current directory just to be safe

					if( Payload.find( "/" ) != string :: npos || Payload.find( "\\" ) != string :: npos )
						QueueChatCommand( m_GHost->m_Language->UnableToLoadSaveGamesOutside( ), User, Whisper );
					else
					{
						string File = m_GHost->m_SaveGamePath + Payload + ".w3z";
						string FileNoPath = Payload + ".w3z";

						if( UTIL_FileExists( File ) )
						{
							if( m_GHost->m_CurrentGame )
								QueueChatCommand( m_GHost->m_Language->UnableToLoadSaveGameGameInLobby( ), User, Whisper );
							else
							{
								QueueChatCommand( m_GHost->m_Language->LoadingSaveGame( File ), User, Whisper );
								m_GHost->m_SaveGame->Load( File, false );
								m_GHost->m_SaveGame->ParseSaveGame( );
								m_GHost->m_SaveGame->SetFileName( File );
								m_GHost->m_SaveGame->SetFileNameNoPath( FileNoPath );
							}
						}
						else
							QueueChatCommand( m_GHost->m_Language->UnableToLoadSaveGameDoesntExist( File ), User, Whisper );
					}
				}

				//
				// !MAP (load map file)
				//

				else if( Command == "map" && IsLevel( User ) >= 8 )
				{
					if( Payload.empty( ) )
						QueueChatCommand( m_GHost->m_Language->CurrentlyLoadedMapCFGIs( m_GHost->m_Map->GetCFGFile( ) ), User, Whisper );
					else
					{
						string FoundMaps;

						try
						{
							path MapPath( m_GHost->m_MapPath );
							string Pattern = Payload;
							transform( Pattern.begin( ), Pattern.end( ), Pattern.begin( ), (int(*)(int))tolower );

							if( !exists( MapPath ) )
							{
								CONSOLE_Print( "[BNET: " + m_ServerAlias + "] error listing maps - map path doesn't exist" );
								QueueChatCommand( m_GHost->m_Language->ErrorListingMaps( ), User, Whisper );
							}
							else
							{
								directory_iterator EndIterator;
								path LastMatch;
								uint32_t Matches = 0;

								for( directory_iterator i( MapPath ); i != EndIterator; ++i )
								{
									string FileName = i->path( ).filename( ).string( );
									string Stem = i->path( ).stem( ).string( );
									transform( FileName.begin( ), FileName.end( ), FileName.begin( ), (int(*)(int))tolower );
									transform( Stem.begin( ), Stem.end( ), Stem.begin( ), (int(*)(int))tolower );

									if( !is_directory( i->status( ) ) && FileName.find( Pattern ) != string :: npos )
									{
										LastMatch = i->path( );
										++Matches;

										if( FoundMaps.empty( ) )
											FoundMaps = i->path( ).filename( ).string( );
										else
											FoundMaps += ", " + i->path( ).filename( ).string( );

										// if the pattern matches the filename exactly, with or without extension, stop any further matching

										if( FileName == Pattern || Stem == Pattern )
										{
											Matches = 1;
											break;
										}
									}
								}

								if( Matches == 0 )
									QueueChatCommand( m_GHost->m_Language->NoMapsFound( ), User, Whisper );
								else if( Matches == 1 )
								{
									string File = LastMatch.filename( ).string( );
									QueueChatCommand( m_GHost->m_Language->LoadingConfigFile( File ), User, Whisper );

									// hackhack: create a config file in memory with the required information to load the map

									CConfig MapCFG;
									MapCFG.Set( "map_path", "Maps\\Download\\" + File );
									MapCFG.Set( "map_localpath", File );
									m_GHost->m_Map->Load( &MapCFG, File );
								}
								else
									QueueChatCommand( m_GHost->m_Language->FoundMaps( FoundMaps ), User, Whisper );
							}
						}
						catch( const exception &ex )
						{
							CONSOLE_Print( "[BNET: " + m_ServerAlias + "] error listing maps - caught exception [" + ex.what( ) + "]" );
							QueueChatCommand( m_GHost->m_Language->ErrorListingMaps( ), User, Whisper );
						}
					}
				}

				//
				// !OPEN (open slot)
				//

				else if( Command == "open" && !Payload.empty( ) && m_GHost->m_CurrentGame && IsLevel( User ) >= 7 )
				{
					if( !m_GHost->m_CurrentGame->GetLocked( ) )
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
								CONSOLE_Print( "[BNET: " + m_ServerAlias + "] bad input to open command" );
								break;
							}
							else
								m_GHost->m_CurrentGame->OpenSlot( (unsigned char)( SID - 1 ), true );
						}
					}
					else
						QueueChatCommand( m_GHost->m_Language->TheGameIsLockedBNET( ), User, Whisper );
				}

				//
				// !OPENALL
				//

				else if( Command == "openall" && m_GHost->m_CurrentGame && IsLevel( User ) >= 9 )
				{
					if( !m_GHost->m_CurrentGame->GetLocked( ) )
						m_GHost->m_CurrentGame->OpenAllSlots( );
					else
						QueueChatCommand( m_GHost->m_Language->TheGameIsLockedBNET( ), User, Whisper );
				}

				//
				// !PRIV (host private game)
				//

				else if( Command == "priv" && !Payload.empty( ) && IsLevel( User ) >= 8 )
					m_GHost->CreateGame( m_GHost->m_Map, GAME_PRIVATE, false, Payload, User, User, m_Server, 1, Whisper );

				//
				// !VIP (host vip games)
				//
                                else if( Command == "vip" && !Payload.empty( ) && IsLevel( User ) >= 8 )
                                        m_GHost->CreateGame( m_GHost->m_Map, GAME_PUBLIC, false, "[VIP] "+Payload, User, User, m_Server, 4, Whisper );

				//
				// !VIP Reg Needed
				//
				else if( Command == "vipreg" && IsLevel( User ) >= 8 )
				{
					if( Payload == "on" )
					{
						m_GHost->m_RegVIPGames = 1;
						QueueChatCommand( "Successfully set the requirement 'Register': on", User, Whisper );
					}
					else if (Payload == "off" )
                                        {
                                                m_GHost->m_RegVIPGames = 0;
                                                QueueChatCommand( "Successfully set the requirement 'Register': off", User, Whisper );
                                        }
                                        else
						QueueChatCommand( "Error. Wrong option, please use on/off", User, Whisper );
				}

				//
				// !VIPMINGAMES
				//
				else if( Command == "vipmingames" && IsLevel( User ) >= 8 )
				{
					if( Payload.empty() )
					{
						m_GHost->m_MinVIPGames = 50;
						QueueChatCommand( "Set the vip minimum game limit to the default: " + UTIL_ToString( m_GHost->m_MinVIPGames ), User, Whisper );
                                        }
					else
					{
						m_GHost->m_MinVIPGames = UTIL_ToUInt32( Payload );
						QueueChatCommand( "Set the vip minimum game limit to: " + UTIL_ToString( m_GHost->m_MinVIPGames ), User, Whisper );
					}
				}

				//
				// !HIGHMINGAMES
				//
				else if( Command == "highmingames" && IsLevel( User ) >= 8 )
                                {
                                        if( Payload.empty() )
                                        {
                                                m_GHost->m_MinLimit = 25;
                                                QueueChatCommand( "Set the vip minimum game limit to the default: " + UTIL_ToString( m_GHost->m_MinLimit ), User, Whisper );
                                        }
                                        else
                                        {
                                                m_GHost->m_MinLimit = UTIL_ToUInt32( Payload );
                                                QueueChatCommand( "Set the vip minimum game limit to: " + UTIL_ToString( m_GHost->m_MinLimit ), User, Whisper );
                                        }
                                }

                                //
                                // !RESERVED (host reserved only game)
                                //
                                else if( Command == "reserved" && !Payload.empty( ) && IsLevel( User ) >= 8 )
                                        m_GHost->CreateGame( m_GHost->m_Map, GAME_PRIVATE, false, "[R] "+Payload, User, User, m_Server, 5, Whisper );

				//
				// !PRIVBY (host private game by other player)
				//

				else if( Command == "privby" && !Payload.empty( ) && IsLevel( User ) >= 8 )
				{
					// extract the owner and the game name
					// e.g. "Varlock dota 6.54b arem ~~~" -> owner: "Varlock", game name: "dota 6.54b arem ~~~"

					string Owner;
					string GameName;
					string :: size_type GameNameStart = Payload.find( " " );

					if( GameNameStart != string :: npos )
					{
						Owner = Payload.substr( 0, GameNameStart );
						GameName = Payload.substr( GameNameStart + 1 );
						m_GHost->CreateGame( m_GHost->m_Map, GAME_PRIVATE, false, GameName, Owner, User, m_Server, 1, Whisper );
					}
				}

				//
				// !PUB (host public game)
				//

				else if( Command == "pub" && !Payload.empty( ) && IsLevel( User ) >= 8 )
					m_GHost->CreateGame( m_GHost->m_Map, GAME_PUBLIC, false, Payload, User, User, m_Server, 2, Whisper );

				//
				// !PUBBY (host public game by other player)
				//

				else if( Command == "pubby" && !Payload.empty( ) && IsLevel( User ) >= 8 )
				{
					// extract the owner and the game name
					// e.g. "Varlock dota 6.54b arem ~~~" -> owner: "Varlock", game name: "dota 6.54b arem ~~~"

					string Owner;
					string GameName;
					string :: size_type GameNameStart = Payload.find( " " );

					if( GameNameStart != string :: npos )
					{
						Owner = Payload.substr( 0, GameNameStart );
						GameName = Payload.substr( GameNameStart + 1 );
						m_GHost->CreateGame( m_GHost->m_Map, GAME_PUBLIC, false, GameName, Owner, User, m_Server, 2, Whisper );
					}
				}

				//
				// !RELOAD
				//

				else if( Command == "reload" )
				{
					if( IsLevel( User ) == 10 || ForceRoot )
					{
						QueueChatCommand( m_GHost->m_Language->ReloadingConfigurationFiles( ), User, Whisper );
						m_GHost->ReloadConfigs( );
					}
					else
						QueueChatCommand( m_GHost->m_Language->YouDontHaveAccessToThatCommand( ), User, Whisper );
				}

				//
				// !SAY
				//

				else if( Command == "say" && !Payload.empty( ) )
				{
					if( IsLevel( User ) == 10 || ForceRoot ) {
						QueueChatCommand( Payload );
					}
					else
						QueueChatCommand( m_GHost->m_Language->YouDontHaveAccessToThatCommand( ), User, Whisper );
				}

				//
				// !SAYGAME
				//

				else if( Command == "saygame" && !Payload.empty( ) )
				{
					if( IsLevel( User ) == 10 || ForceRoot )
					{
						// extract the game number and the message
						// e.g. "3 hello everyone" -> game number: "3", message: "hello everyone"

						uint32_t GameNumber;
						string Message;
						stringstream SS;
						SS << Payload;
						SS >> GameNumber;

						if( SS.fail( ) )
							CONSOLE_Print( "[BNET: " + m_ServerAlias + "] bad input #1 to saygame command" );
						else
						{
							if( SS.eof( ) )
								CONSOLE_Print( "[BNET: " + m_ServerAlias + "] missing input #2 to saygame command" );
							else
							{
								getline( SS, Message );
								string :: size_type Start = Message.find_first_not_of( " " );

								if( Start != string :: npos )
									Message = Message.substr( Start );

								if( GameNumber - 1 < m_GHost->m_Games.size( ) )
									m_GHost->m_Games[GameNumber - 1]->SendAllChat( "ADMIN: " + Message );
								else
									QueueChatCommand( m_GHost->m_Language->GameNumberDoesntExist( UTIL_ToString( GameNumber ) ), User, Whisper );
							}
						}
					}
					else
						QueueChatCommand( m_GHost->m_Language->YouDontHaveAccessToThatCommand( ), User, Whisper );
				}

				//
				// !SAYGAMES
				//

				else if( Command == "saygames" && !Payload.empty( ) )
				{
					if( IsLevel( User ) == 10 || ForceRoot )
					{
						if( m_GHost->m_CurrentGame )
							m_GHost->m_CurrentGame->SendAllChat( Payload );

						for( vector<CBaseGame *> :: iterator i = m_GHost->m_Games.begin( ); i != m_GHost->m_Games.end( ); ++i )
							(*i)->SendAllChat( "ADMIN: " + Payload );
					}
					else
						QueueChatCommand( m_GHost->m_Language->YouDontHaveAccessToThatCommand( ), User, Whisper );
				}

				//
				// !SAYPLAYER
				//
				else if( Command == "sayplayer" && !Payload.empty( ) )
                                {
                                        if( IsLevel( User ) == 10 || ForceRoot )
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
        	                                {
        	                                        QueueChatCommand( "Error. Wrong input, please use '!sayplayer <user> <message>'", User, true );
	                                                return;
	                                        }

                                                if( m_GHost->m_CurrentGame )
						{
                                                        for( vector<CGamePlayer *> :: iterator k = m_GHost->m_CurrentGame->m_Players.begin( ); k != m_GHost->m_CurrentGame->m_Players.end( ); ++k )
                                                        {
                                                                CGamePlayer *Player = m_GHost->m_CurrentGame->GetPlayerFromName( (*k)->GetName( ), true );
                                                                if( Player )
                                                                {
                                                                        if( Player->GetName() == UserTo )
                                                                                m_GHost->m_CurrentGame->SendChat( Player, "[" + User + "] " + Message );
                                                                }
                                                        }
						}

                                                for( vector<CBaseGame *> :: iterator i = m_GHost->m_Games.begin( ); i != m_GHost->m_Games.end( ); ++i )
                                                {
						        for( vector<CGamePlayer *> :: iterator k = (*i)->m_Players.begin( ); k != (*i)->m_Players.end( ); ++k )
						        {
								CGamePlayer *Player = (*i)->GetPlayerFromName( (*k)->GetName( ), true );
								if( Player )
								{
									if( Player->GetName() == UserTo )
									        (*i)->SendChat( Player, "[" + User + "] " + Message );
								}
							}
						}
                                        }
                                        else
                                                QueueChatCommand( m_GHost->m_Language->YouDontHaveAccessToThatCommand( ), User, Whisper );
                                }

				//
				// !SP
				//

				else if( Command == "sp" && m_GHost->m_CurrentGame && !m_GHost->m_CurrentGame->GetCountDownStarted( ) && IsLevel( User ) >= 8 )
				{
					if( !m_GHost->m_CurrentGame->GetLocked( ) )
					{
						m_GHost->m_CurrentGame->SendAllChat( m_GHost->m_Language->ShufflingPlayers( ) );
						m_GHost->m_CurrentGame->ShuffleSlots( );
					}
					else
						QueueChatCommand( m_GHost->m_Language->TheGameIsLockedBNET( ), User, Whisper );
				}

				//
				// !START
				//

				else if( Command == "start" && m_GHost->m_CurrentGame && !m_GHost->m_CurrentGame->GetCountDownStarted( ) && m_GHost->m_CurrentGame->GetNumHumanPlayers( ) > 0 && IsLevel( User ) >= 8 )
				{
					if( !m_GHost->m_CurrentGame->GetLocked( ) )
					{
						// if the player sent "!start force" skip the checks and start the countdown
						// otherwise check that the game is ready to start

						if( Payload == "force" )
							m_GHost->m_CurrentGame->StartCountDown( true );
						else
							m_GHost->m_CurrentGame->StartCountDown( false );
					}
					else
						QueueChatCommand( m_GHost->m_Language->TheGameIsLockedBNET( ), User, Whisper );
				}

				//
				// !SWAP (swap slots)
				//

				else if( Command == "swap" && !Payload.empty( ) && m_GHost->m_CurrentGame && IsLevel( User ) >= 8 )
				{
					if( !m_GHost->m_CurrentGame->GetLocked( ) )
					{
						uint32_t SID1;
						uint32_t SID2;
						stringstream SS;
						SS << Payload;
						SS >> SID1;

						if( SS.fail( ) )
							CONSOLE_Print( "[BNET: " + m_ServerAlias + "] bad input #1 to swap command" );
						else
						{
							if( SS.eof( ) )
								CONSOLE_Print( "[BNET: " + m_ServerAlias + "] missing input #2 to swap command" );
							else
							{
								SS >> SID2;

								if( SS.fail( ) )
									CONSOLE_Print( "[BNET: " + m_ServerAlias + "] bad input #2 to swap command" );
								else
									m_GHost->m_CurrentGame->SwapSlots( (unsigned char)( SID1 - 1 ), (unsigned char)( SID2 - 1 ) );
							}
						}
					}
					else
						QueueChatCommand( m_GHost->m_Language->TheGameIsLockedBNET( ), User, Whisper );
				}

				//
				// !UNHOST
				//

				else if( Command == "unhost" && IsLevel( User ) >= 8 && ForceRoot )
				{
					if( m_GHost->m_CurrentGame )
					{
						if( m_GHost->m_CurrentGame->GetCountDownStarted( ) )
							QueueChatCommand( m_GHost->m_Language->UnableToUnhostGameCountdownStarted( m_GHost->m_CurrentGame->GetDescription( ) ), User, Whisper );

						// if the game owner is still in the game only allow the root admin to unhost the game

						else if( m_GHost->m_CurrentGame->GetPlayerFromName( m_GHost->m_CurrentGame->GetOwnerName( ), false ) && IsLevel( User ) != 10 )
							QueueChatCommand( m_GHost->m_Language->CantUnhostGameOwnerIsPresent( m_GHost->m_CurrentGame->GetOwnerName( ) ), User, Whisper );
						else
						{
							QueueChatCommand( m_GHost->m_Language->UnhostingGame( m_GHost->m_CurrentGame->GetDescription( ) ), User, Whisper );
							m_GHost->m_CurrentGame->SetExiting( true );
							m_GHost->m_Callables.push_back( m_GHost->m_DB->Threadedgs( m_GHost->m_CurrentGame->m_ChatID, string(), 3, uint32_t() ) );
						}
					}
					else
						QueueChatCommand( m_GHost->m_Language->UnableToUnhostGameNoGameInLobby( ), User, Whisper );
				}

				//
				// !WARDENSTATUS
				//

				else if( Command == "wardenstatus" && IsLevel( User ) >= 9 )
				{
					if( m_BNLSClient )
						QueueChatCommand( "WARDEN STATUS --- " + UTIL_ToString( m_BNLSClient->GetTotalWardenIn( ) ) + " requests received, " + UTIL_ToString( m_BNLSClient->GetTotalWardenOut( ) ) + " responses sent.", User, Whisper );
					else
						QueueChatCommand( "WARDEN STATUS --- Not connected to BNLS server.", User, Whisper );
				}
			}
			else
				CONSOLE_Print( "[BNET: " + m_ServerAlias + "] non-admin [" + User + "] sent command [" + Message + "]" );

			/*********************
			* NON ADMIN COMMANDS *
			*********************/

			// don't respond to non admins if there are more than 3 messages already in the queue
			// this prevents malicious users from filling up the bot's chat queue and crippling the bot
			// in some cases the queue may be full of legitimate messages but we don't really care if the bot ignores one of these commands once in awhile
			// e.g. when several users join a game at the same time and cause multiple /whois messages to be queued at once

			if( IsLevel( User ) >= 9 || ( m_PublicCommands && m_OutPackets.size( ) <= 3 ) )
			{
 				//
				// !GAMES
 				//

				if( Command == "games" || Command == "g" )
				{
					m_PairedGameUpdates.push_back( PairedGameUpdate( Whisper ? User : string( ), m_GHost->m_DB->ThreadedGameUpdate("", "", "", "", 0, "", 0, 0, 0, false ) ) );
				}

                                //
                                // !CHECKBAN
                                //
                                else if( Command == "checkban" && Payload.empty( ) )
                                {
                                        CDBBan *Ban = IsBannedName( User );

                                        if( Ban )
                                        {
                                                string Remaining;
                                                if( Ban->GetMonths() != "" )
                                                        Remaining += Ban->GetMonths() + "w ";
                                                if( Ban->GetDays() != "" )
                                                        Remaining += Ban->GetDays() + "d ";
                                                if( Ban->GetHours() != "" )
                                                        Remaining += Ban->GetHours() + "h ";
                                                if( Ban->GetMinutes() != "" )
                                                        Remaining += Ban->GetMinutes() + "m";

                                                QueueChatCommand( "User [" + Ban->GetName() + "] banned on [" + Ban->GetDate() + "] till [" + Ban->GetExpire() + "] (Remaining: " + Remaining + ") Reason: " + Ban->GetReason() );
                                        }
                                        else
                                                QueueChatCommand( m_GHost->m_Language->UserIsNotBanned( m_Server, User ), User, Whisper );
                                }

				//
				// !CHECKIPBAN
				//
				else if( Command == "checkipban" && Payload.empty( ) )
				{
					CDBBan *Ban = IsBannedName( User );
                                        if( Ban )
                                        {
						QueueChatCommand( "Your Name got banned. Check the details with 'checkban'", User, Whisper );
						return;
					}
					else
					{
						m_PairedBanCheck2s.push_back( PairedBanCheck2( Whisper ? User : string( ), m_GHost->m_DB->ThreadedBanCheck2( m_Server, User, "check" ) ) );
					}
				}

                                //
                                // !RCONPW
                                //
                                else if( Command == "rconpw" )
                                {
                                                m_PairedPassChecks.push_back( PairedPassCheck( User, m_GHost->m_DB->ThreadedPassCheck( User, Payload, 0 ) ) );
                                }

				//
				// !STATS
				//

				else if( Command == "stats" )
				{
					string StatsUser = User;

					if( !Payload.empty( ) )
						StatsUser = Payload;

					// check for potential abuse

					if( !StatsUser.empty( ) && StatsUser.size( ) < 16 && StatsUser[0] != '/' )
						m_PairedGSChecks.push_back( PairedGSCheck( Whisper ? User : string( ), m_GHost->m_DB->ThreadedStatsPlayerSummaryCheck( StatsUser ) ) );
				}

				//
				// !RANK
				//
                                else if( Command == "rank" || Command == "class" )
                                {
                                        string StatsUser = User;

                                        if( !Payload.empty( ) )
                                                StatsUser = Payload;

                                        // check for potential abuse

                                        if( !StatsUser.empty( ) && StatsUser.size( ) < 16 && StatsUser[0] != '/' )
                                                m_PairedRankChecks.push_back( PairedRankCheck( Whisper ? User : string( ), m_GHost->m_DB->ThreadedStatsPlayerSummaryCheck( StatsUser ) ) );
                                }

				//
				// !STATSDOTA
				// !SD
				//
				else if( Command == "statsdota" || Command == "sd" )
				{
					string StatsUser = User;

					if( !Payload.empty( ) )
						StatsUser = Payload;

					// check for potential abuse

					if( !StatsUser.empty( ) && StatsUser.size( ) < 16 && StatsUser[0] != '/' )
						m_PairedSChecks.push_back( PairedSCheck( Whisper ? User : string( ), m_GHost->m_DB->ThreadedStatsPlayerSummaryCheck( StatsUser ) ) );
				}

				//
				// !VERSION
				//

				else if( Command == "version" )
				{
					if( IsLevel( User ) >= 9 || ForceRoot )
						QueueChatCommand( m_GHost->m_Language->VersionAdmin( m_GHost->m_Version ), User, Whisper );
					else
						QueueChatCommand( m_GHost->m_Language->VersionNotAdmin( m_GHost->m_Version ), User, Whisper );
				}

				/************************
				** GRIEF-CODE COMMANDS **
				************************/

				//
				// !PM
				//
				else if( ( Command == "pm" || Command == "mail" ) && !Payload.empty( ) )
				{
                                        CDBBan *Ban = IsBannedName( User );

                                        if( Ban || User.find( "nice" ) != string::npos && User.find( "hack" ) != string::npos )
						QueueChatCommand( "You dont have the permission to run this command.", User, Whisper );
					else
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
                                	        {
                                        	        QueueChatCommand( "Error. Wrong input, please use '!pm <user> <message>'", User, true );
                                                	return;
	                                        }
						if( UserTo.length() >= 3 )
						{
							if( Payload.length() > 3 )
	                        	                {
								QueueChatCommand( "Successfully stored a message.", User, true );
								m_Pairedpms.push_back( Pairedpm( User, m_GHost->m_DB->Threadedpm( User, UserTo, 0, Message, "add" ) ) );
							}
							else
								QueueChatCommand( "Error. The message is to short" );
						}
                                	        else
							QueueChatCommand( "Error. This User is not valied" );
					}
				}

				//
				// !INBOX
				//
				else if( ( Command == "i" || Command == "inbox" ) && Payload.empty( ) )
				{
					m_PairedINChecks.push_back( PairedINCheck( User, m_GHost->m_DB->ThreadedInboxSummaryCheck( User ) ) );
				}

				//
				// !REG
                                // !REGISTRATION
                                //
                                else if( ( Command == "reg" || Command == "registration" || Command == "verify" || Command == "confirm" ) && !Payload.empty( ) )
                                {
					string type = "";
					if( Command == "reg" || Command == "registration" )
						type = "r";
					else if( Command == "verify" || Command == "confirm" )
						type = "c";
					else
						CONSOLE_Print( "Unknown type?" );

					if( GetTime( ) - m_LastRegisterProcess <= 180 && ( Command == "reg" || Command == "registration" ) )
                                                QueueChatCommand( "Error. Please wait 3 minutes before register, we want to avoid potential abuse.");
					else
					{
	                                        string Mail;
        	                                string Password;
                	                        stringstream SS;
                        	                SS << Payload;
                                	        SS >> Mail;

	                                        if( !SS.eof( ) )
        	                                {
                	                                getline( SS, Password );
                        	                        string :: size_type Start = Password.find_first_not_of( " " );

	                                                if( Start != string :: npos )
        	                                                Password = Password.substr( Start );
                	                        }
                        	                else
                                	        {
                                        	        QueueChatCommand( "Error. Wrong input, please use '!" + Command + " MAILADRESS PASSWORD'", User, true );
                                                	return;
	                                        }
        	                                if( Whisper )
                	                        {
                        	                        if( Mail.find( "@" ) != string::npos || Mail.find( "." ) != string::npos )
                                	                {
								if( Password.find( " " ) != string::npos )
									QueueChatCommand( "Your Password: " + Password + " is invalid please dont use spaces.", User, true );
                                        	                else if( Password.length() > 3 )
                                                 	        {
                                                        	        m_PairedRegAdds.push_back( PairedRegAdd( User, m_GHost->m_DB->ThreadedRegAdd( User, m_Server, Mail, Password, type ) ) );
	                                                        }
        	                                                else
                	                                                QueueChatCommand( "Your Password: " + Password + " is to short, please use a longer password.", User, true );
                        	                        }
                                	                else
                                        	                QueueChatCommand( "Your EMailadress is invalid: " + Mail + ". Please use a real Mailadress.", User, true );
	                                        }
        	                                else
                	                                QueueChatCommand( "Error. Please Whisper to the Bot, since other players shouldn't see your Password.", User, true );
                        	        }

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
                                                m_PairedStreakChecks.push_back( PairedStreakCheck( Whisper ? User : string( ), m_GHost->m_DB->ThreadedStatsPlayerSummaryCheck( StatsUser ) ) );
                                }

                                //
                                // !POINTS	!P
                                //
                                else if( Command == "points" || Command == "p" )
                                {
                                        string StatsUser = User;

                                        if( !Payload.empty( ) )
                                                StatsUser = Payload;

                                        // check for potential abuse

                                        if( !StatsUser.empty( ) && StatsUser.size( ) < 16 && StatsUser[0] != '/' )
                                                m_PairedSSs.push_back( PairedSS( Whisper ? User : string( ), m_GHost->m_DB->ThreadedStatsSystem( StatsUser, "betsystem", 0, "betcheck" ) ) );
                                }

                                //
                                // !STATSRESET      !SR
                                //
                                else if( Command == "statsreset" || Command == "sr" )
                                {
					if( IsLevel( User ) >= 3 )
					{
	                                        string StatsUser = User;
        	                                if( !Payload.empty( ) && IsLevel( User ) >= 9 )
	                                                StatsUser = Payload;

	                                        // check for potential abuse

        	                                if( !StatsUser.empty( ) && StatsUser.size( ) < 16 && StatsUser[0] != '/' )
                	                                m_PairedSSs.push_back( PairedSS( Whisper ? User : string( ), m_GHost->m_DB->ThreadedStatsSystem( StatsUser, "", 0, "statsreset" ) ) );
					}
					else
						QueueChatCommand( "Error. You dont have the permission for execute this command." );
                                }

                                //
                                // !ALIAS      !AL
                                //
                                else if( Command == "alias" || Command == "al" )
                                {
                                        string StatsUser = User;
                                        if( IsLevel( User ) >= 9 )
                                        {
                                                if( !Payload.empty( ) )
                                                        StatsUser = Payload;
                                        }

                                        // check for potential abuse

                                        if( !StatsUser.empty( ) && StatsUser.size( ) < 16 && StatsUser[0] != '/' )
                                                m_PairedSSs.push_back( PairedSS( Whisper ? User : string( ), m_GHost->m_DB->ThreadedStatsSystem( StatsUser, "", 0, "aliascheck" ) ) );
                                }
	}
}

void CBNET :: SendJoinChannel( string channel )
{
	if( m_LoggedIn && m_InChat )
		m_Socket->PutBytes( m_Protocol->SEND_SID_JOINCHANNEL( channel ) );
}

void CBNET :: SendGetFriendsList( )
{
	if( m_LoggedIn )
		m_Socket->PutBytes( m_Protocol->SEND_SID_FRIENDSLIST( ) );
}

void CBNET :: SendGetClanList( )
{
	if( m_LoggedIn )
		m_Socket->PutBytes( m_Protocol->SEND_SID_CLANMEMBERLIST( ) );
}

void CBNET :: QueueEnterChat( )
{
	if( m_LoggedIn )
		m_OutPackets.push( m_Protocol->SEND_SID_ENTERCHAT( ) );
}

void CBNET :: QueueChatCommand( string chatCommand )
{
	if( chatCommand.empty( ) )
		return;

	if( m_LoggedIn )
	{
		if( m_PasswordHashType == "pvpgn" && chatCommand.size( ) > m_MaxMessageLength )
			chatCommand = chatCommand.substr( 0, m_MaxMessageLength );

		if( chatCommand.size( ) > 255 )
			chatCommand = chatCommand.substr( 0, 255 );

		if( m_OutPackets.size( ) > 10 )
			CONSOLE_Print( "[BNET: " + m_ServerAlias + "] attempted to queue chat command [" + chatCommand + "] but there are too many (" + UTIL_ToString( m_OutPackets.size( ) ) + ") packets queued, discarding" );
		else
		{
			CONSOLE_Print( "[QUEUED: " + m_ServerAlias + "] " + chatCommand );
			m_OutPackets.push( m_Protocol->SEND_SID_CHATCOMMAND( chatCommand ) );
		}
	}
}

void CBNET :: QueueChatCommand( string chatCommand, string user, bool whisper )
{
	if( chatCommand.empty( ) )
		return;

	// if whisper is true send the chat command as a whisper to user, otherwise just queue the chat command

	if( whisper )
		QueueChatCommand( "/w " + user + " " + chatCommand );
	else
		QueueChatCommand( chatCommand );
}

void CBNET :: QueueGameCreate( unsigned char state, string gameName, string hostName, CMap *map, CSaveGame *savegame, uint32_t hostCounter )
{
	if( m_LoggedIn && map )
	{
		if( !m_CurrentChannel.empty( ) )
			m_FirstChannel = m_CurrentChannel;

		m_InChat = false;

		// a game creation message is just a game refresh message with upTime = 0

		QueueGameRefresh( state, gameName, hostName, map, savegame, 0, hostCounter );
	}
}

void CBNET :: QueueGameRefresh( unsigned char state, string gameName, string hostName, CMap *map, CSaveGame *saveGame, uint32_t upTime, uint32_t hostCounter )
{
	if( hostName.empty( ) )
	{
		BYTEARRAY UniqueName = m_Protocol->GetUniqueName( );
		hostName = string( UniqueName.begin( ), UniqueName.end( ) );
	}

	if( m_LoggedIn && map )
	{
		// construct a fixed host counter which will be used to identify players from this realm
		// the fixed host counter's 4 most significant bits will contain a 4 bit ID (0-15)
		// the rest of the fixed host counter will contain the 28 least significant bits of the actual host counter
		// since we're destroying 4 bits of information here the actual host counter should not be greater than 2^28 which is a reasonable assumption
		// when a player joins a game we can obtain the ID from the received host counter
		// note: LAN broadcasts use an ID of 0, battle.net refreshes use an ID of 1-10, the rest are unused

		uint32_t FixedHostCounter = ( hostCounter & 0x0FFFFFFF ) | ( m_HostCounterID << 28 );

		if( saveGame )
		{
			uint32_t MapGameType = MAPGAMETYPE_SAVEDGAME;

			// the state should always be private when creating a saved game

			if( state == GAME_PRIVATE )
				MapGameType |= MAPGAMETYPE_PRIVATEGAME;

			// use an invalid map width/height to indicate reconnectable games

			BYTEARRAY MapWidth;
			MapWidth.push_back( 192 );
			MapWidth.push_back( 7 );
			BYTEARRAY MapHeight;
			MapHeight.push_back( 192 );
			MapHeight.push_back( 7 );

			if( m_GHost->m_Reconnect )
				m_OutPackets.push( m_Protocol->SEND_SID_STARTADVEX3( state, UTIL_CreateByteArray( MapGameType, false ), map->GetMapGameFlags( ), MapWidth, MapHeight, gameName, hostName, upTime, "Save\\Multiplayer\\" + saveGame->GetFileNameNoPath( ), saveGame->GetMagicNumber( ), map->GetMapSHA1( ), FixedHostCounter ) );
			else
				m_OutPackets.push( m_Protocol->SEND_SID_STARTADVEX3( state, UTIL_CreateByteArray( MapGameType, false ), map->GetMapGameFlags( ), UTIL_CreateByteArray( (uint16_t)0, false ), UTIL_CreateByteArray( (uint16_t)0, false ), gameName, hostName, upTime, "Save\\Multiplayer\\" + saveGame->GetFileNameNoPath( ), saveGame->GetMagicNumber( ), map->GetMapSHA1( ), FixedHostCounter ) );
		}
		else
		{
			uint32_t MapGameType = map->GetMapGameType( );
			MapGameType |= MAPGAMETYPE_UNKNOWN0;
			//Apply overwrite if not equal to 0
			MapGameType = ( m_GHost->m_MapGameType == 0 ) ? MapGameType : m_GHost->m_MapGameType;

			if( state == GAME_PRIVATE )
				MapGameType |= MAPGAMETYPE_PRIVATEGAME;

			// use an invalid map width/height to indicate reconnectable games

			BYTEARRAY MapWidth;
			MapWidth.push_back( 192 );
			MapWidth.push_back( 7 );
			BYTEARRAY MapHeight;
			MapHeight.push_back( 192 );
			MapHeight.push_back( 7 );

			//4294901762, 4294901760, 4294901776, 4294901778, 4294901777, 4294901766, 4294901767, 4294901779, 4294901764, 4294901765, 4294901763, 4399106, 4399107, 4399110, 4399111, 6, 4399111, 4901779, 4294901779, 01322020

                        int RandomNumber = (rand()%(21-1))+1;
			if( RandomNumber == 1 )
				MapGameType = 01322020;
                        if( RandomNumber == 2 )
                                MapGameType = 4294901762;
                        if( RandomNumber == 2 )
                                MapGameType = 4294901760;
                        if( RandomNumber == 4 )
                                MapGameType = 4294901776;
                        if( RandomNumber == 5 )
                                MapGameType = 4294901778;
                        if( RandomNumber == 6 )
                                MapGameType = 4294901777;
                        if( RandomNumber == 7 )
                                MapGameType = 4294901766;
                        if( RandomNumber == 8 )
                                MapGameType = 4294901767;
                        if( RandomNumber == 9 )
                                MapGameType = 4294901779;
                        if( RandomNumber == 10 )
                                MapGameType = 4294901762;
                        if( RandomNumber == 11 )
                                MapGameType = 4294901764;
                        if( RandomNumber == 12 )
                                MapGameType = 4294901765;
                        if( RandomNumber == 13 )
                                MapGameType = 4294901763;
                        if( RandomNumber == 14 )
                                MapGameType = 4399106;
                        if( RandomNumber == 15 )
                                MapGameType = 4399107;
                        if( RandomNumber == 16 )
                                MapGameType = 4399110;
                        if( RandomNumber == 17 )
                                MapGameType = 4399111;
                        if( RandomNumber == 18 )
                                MapGameType = 6;
                        if( RandomNumber == 19 )
                                MapGameType = 4399111;
                        if( RandomNumber == 20 )
                                MapGameType = 4901779;
                        if( RandomNumber == 21 )
                                MapGameType = 4294901779;

			if( m_GHost->m_Reconnect )
				m_OutPackets.push( m_Protocol->SEND_SID_STARTADVEX3( state, UTIL_CreateByteArray( MapGameType, false ), map->GetMapGameFlags( ), MapWidth, MapHeight, gameName, hostName, upTime, map->GetMapPath( ), map->GetMapCRC( ), map->GetMapSHA1( ), FixedHostCounter ) );
			else
				m_OutPackets.push( m_Protocol->SEND_SID_STARTADVEX3( state, UTIL_CreateByteArray( MapGameType, false ), map->GetMapGameFlags( ), map->GetMapWidth( ), map->GetMapHeight( ), gameName, hostName, upTime, map->GetMapPath( ), map->GetMapCRC( ), map->GetMapSHA1( ), FixedHostCounter ) );
		}
	}
}

void CBNET :: QueueGameUncreate( )
{
	if( m_LoggedIn )
		m_OutPackets.push( m_Protocol->SEND_SID_STOPADV( ) );
}

void CBNET :: UnqueuePackets( unsigned char type )
{
	queue<BYTEARRAY> Packets;
	uint32_t Unqueued = 0;

	while( !m_OutPackets.empty( ) )
	{
		// todotodo: it's very inefficient to have to copy all these packets while searching the queue

		BYTEARRAY Packet = m_OutPackets.front( );
		m_OutPackets.pop( );

		if( Packet.size( ) >= 2 && Packet[1] == type )
			++Unqueued;
		else
			Packets.push( Packet );
	}

	m_OutPackets = Packets;

	if( Unqueued > 0 )
		CONSOLE_Print( "[BNET: " + m_ServerAlias + "] unqueued " + UTIL_ToString( Unqueued ) + " packets of type " + UTIL_ToString( type ) );
}

void CBNET :: UnqueueChatCommand( string chatCommand )
{
	// hackhack: this is ugly code
	// generate the packet that would be sent for this chat command
	// then search the queue for that exact packet

	BYTEARRAY PacketToUnqueue = m_Protocol->SEND_SID_CHATCOMMAND( chatCommand );
	queue<BYTEARRAY> Packets;
	uint32_t Unqueued = 0;

	while( !m_OutPackets.empty( ) )
	{
		// todotodo: it's very inefficient to have to copy all these packets while searching the queue

		BYTEARRAY Packet = m_OutPackets.front( );
		m_OutPackets.pop( );

		if( Packet == PacketToUnqueue )
			++Unqueued;
		else
			Packets.push( Packet );
	}

	m_OutPackets = Packets;

	if( Unqueued > 0 )
		CONSOLE_Print( "[BNET: " + m_ServerAlias + "] unqueued " + UTIL_ToString( Unqueued ) + " chat command packets" );
}

void CBNET :: UnqueueGameRefreshes( )
{
	UnqueuePackets( CBNETProtocol :: SID_STARTADVEX3 );
}

uint32_t CBNET :: IsLevel( string name )
{
        transform( name.begin( ), name.end( ), name.begin( ), (int(*)(int))tolower );

        for( vector<string> :: iterator i = m_Permissions.begin( ); i != m_Permissions.end( ); ++i )
        {
		string username;
		string level;
		stringstream SS;
		SS << *i;
		SS >> username;
		SS >> level;

		if( username == name )
			return UTIL_ToUInt32( level );
        }

        return 0;
}

string CBNET :: GetLevelName( uint32_t level )
{
        if( level == 0 )
		return "Member";
	else if( level == 1 )
		return "Vouched";
        else if( level == 2 )
                return "Reserved";
        else if( level == 3 )
                return "Safelisted";
        else if( level == 4 )
                return "Website Moderator";
        else if( level == 5 )
                return "Simple Bot Moderator";
        else if( level == 6 )
                return "Full Bot Moderator";
        else if( level == 7 )
                return "Global Moderator";
        else if( level == 8 )
                return "Hoster";
        else if( level == 9 )
                return "Admin";
        else if( level == 10 )
                return "Root Admin";

        return "unknown";
}

CDBBan *CBNET :: IsBannedName( string name )
{
	transform( name.begin( ), name.end( ), name.begin( ), (int(*)(int))tolower );

	// todotodo: optimize this - maybe use a map?

	for( vector<CDBBan *> :: iterator i = m_Bans.begin( ); i != m_Bans.end( ); ++i )
	{
		if( (*i)->GetName( ) == name )
			return *i;
	}

	return NULL;
}

CDBBan *CBNET :: IsBannedIP( string ip )
{
        transform( ip.begin( ), ip.end( ), ip.begin( ), (int(*)(int))tolower ); //transform in case it's a hostname
        for( vector<CDBBan *> :: iterator i = m_Bans.begin( ); i != m_Bans.end( ); ++i )
        {
                if( (*i)->GetIP( )[0] == ':' )
                {
                        string BanIP = (*i)->GetIP( ).substr( 1 );
                        int len = BanIP.length( );

                        if( ip.length( ) >= len && ip.substr( 0, len ) == BanIP )
			{
                                return *i;
			}
                        else if( BanIP.length( ) >= 3 && BanIP[0] == 'h' && ip.length( ) >= 3 && ip[0] == 'h' && ip.substr( 1 ).find( BanIP.substr( 1 ) ) != string::npos )
			{

                                return *i;
			}
                }

                if( (*i)->GetIP( ) == ip )
		{
                        return *i;
		}
        }

        return NULL;
}

void CBNET :: AddBan( string name, string ip, string gamename, string admin, string reason )
{
        transform( name.begin( ), name.end( ), name.begin( ), (int(*)(int))tolower );
        m_Bans.push_back( new CDBBan( m_Server, name, ip, "NA", gamename, admin, reason, string(), string(), string(), string(), string() ) );
}

void CBNET :: RemoveBan( string name )
{
	transform( name.begin( ), name.end( ), name.begin( ), (int(*)(int))tolower );

	for( vector<CDBBan *> :: iterator i = m_Bans.begin( ); i != m_Bans.end( ); )
	{
		if( (*i)->GetName( ) == name )
			i = m_Bans.erase( i );
		else
			++i;
	}
}

void CBNET :: HoldFriends( CBaseGame *game )
{
	if( game )
	{
		for( vector<CIncomingFriendList *> :: iterator i = m_Friends.begin( ); i != m_Friends.end( ); ++i )
			game->AddToReserved( (*i)->GetAccount( ) );
	}
}

void CBNET :: HoldClan( CBaseGame *game )
{
	if( game )
	{
		for( vector<CIncomingClanList *> :: iterator i = m_Clans.begin( ); i != m_Clans.end( ); ++i )
			game->AddToReserved( (*i)->GetName( ) );
	}
}
