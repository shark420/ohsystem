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

#ifndef BNET_H
#define BNET_H

//
// CBNET
//

class CTCPClient;
class CCommandPacket;
class CBNCSUtilInterface;
class CBNETProtocol;
class CBNLSClient;
class CIncomingFriendList;
class CIncomingClanList;
class CIncomingChatEvent;
class CCallableRegAdd;
class CCallableStatsSystem;
class CCallablepm;
class CCallablePassCheck;
class CCallablepenp;
class CCallablePList;
class CCallableBanCount;
class CCallableBanCheck2;
class CCallableBanAdd;
class CCallablePUp;
class CCallableBanRemove;
class CCallableBanList;
class CCallableTBRemove;
class CCallableGamePlayerSummaryCheck;
class CCallableStatsPlayerSummaryCheck;
class CCallableInboxSummaryCheck;
class CCallableDotAPlayerSummaryCheck;
class CDBBan;
class CCallableGameUpdate;

typedef pair<string,CCallablePassCheck *> PairedPassCheck;
typedef pair<string,CCallableRegAdd *> PairedRegAdd;
typedef pair<string,CCallableStatsSystem *> PairedSS;
typedef pair<string,CCallablepm *> Pairedpm;
typedef pair<string,CCallablepenp *> Pairedpenp;
typedef pair<string,CCallableBanCount *> PairedBanCount;
typedef pair<string,CCallableBanCheck2 *> PairedBanCheck2;
typedef pair<string,CCallableBanAdd *> PairedBanAdd;
typedef pair<string,CCallablePUp *> PairedPUp;
typedef pair<string,CCallableBanRemove *> PairedBanRemove;
typedef pair<string,CCallableStatsPlayerSummaryCheck *> PairedGSCheck;
typedef pair<string,CCallableStatsPlayerSummaryCheck *> PairedStreakCheck;
typedef pair<string,CCallableInboxSummaryCheck *> PairedINCheck;
typedef pair<string,CCallableStatsPlayerSummaryCheck *> PairedSCheck;
typedef pair<string,CCallableGameUpdate *> PairedGameUpdate;
typedef pair<string,CCallableStatsPlayerSummaryCheck *> PairedRankCheck;

class CBNET
{
public:
	CGHost *m_GHost;

private:
	CTCPClient *m_Socket;							// the connection to battle.net
	CBNETProtocol *m_Protocol;						// battle.net protocol
	CBNLSClient *m_BNLSClient;						// the BNLS client (for external warden handling)
	queue<CCommandPacket *> m_Packets;				// queue of incoming packets
	CBNCSUtilInterface *m_BNCSUtil;					// the interface to the bncsutil library (used for logging into battle.net)
	queue<BYTEARRAY> m_OutPackets;					// queue of outgoing packets to be sent (to prevent getting kicked for flooding)
	vector<CIncomingFriendList *> m_Friends;		// vector of friends
	vector<CIncomingClanList *> m_Clans;			// vector of clan members
	vector<PairedRegAdd> m_PairedRegAdds;               // vector of paired threaded database reg adds in progress
	vector<PairedSS> m_PairedSSs;
        vector<Pairedpm> m_Pairedpms;               // vector of paired threaded database update for personal message in progress
        vector<Pairedpenp> m_Pairedpenps;
	vector<PairedBanCount> m_PairedBanCounts;		// vector of paired threaded database ban counts in progress
        vector<PairedBanCheck2> m_PairedBanCheck2s;
	vector<PairedBanAdd> m_PairedBanAdds;			// vector of paired threaded database ban adds in progress
	vector<PairedPUp> m_PairedPUps;                   // vector of paired threaded database permission changes in progress
	vector<PairedBanRemove> m_PairedBanRemoves;		// vector of paired threaded database ban removes in progress
	vector<PairedGSCheck> m_PairedGSChecks;		// vector of paired threaded database game player summary checks in progress
        vector<PairedRankCheck> m_PairedRankChecks;
        vector<PairedStreakCheck> m_PairedStreakChecks;               // vector of paired threaded database streak in progress
	vector<PairedINCheck> m_PairedINChecks;               // vector of paired threaded database inbox checks in progress
	vector<PairedSCheck> m_PairedSChecks;		// vector of paired threaded database DotA player summary checks in progress
	vector<PairedGameUpdate> m_PairedGameUpdates;	// vector of paired threaded database gamelist queue
        vector<PairedPassCheck> m_PairedPassChecks;       // vector of paired threaded database password checks in progress
        CCallablePList *m_CallablePList;                // threaded database permission list in progress
	CCallableBanList *m_CallableBanList;			// threaded database ban list in progress
	CCallableTBRemove *m_CallableTBRemove;
	vector<CDBBan *> m_Bans;						// vector of cached bans
	bool m_Exiting;									// set to true and this class will be deleted next update
	string m_Server;								// battle.net server to connect to
	string m_ServerIP;								// battle.net server to connect to (the IP address so we don't have to resolve it every time we connect)
	string m_ServerAlias;							// battle.net server alias (short name, e.g. "USEast")
	string m_BNLSServer;							// BNLS server to connect to (for warden handling)
	uint16_t m_BNLSPort;							// BNLS port
	uint32_t m_BNLSWardenCookie;					// BNLS warden cookie
	string m_CDKeyROC;								// ROC CD key
	string m_CDKeyTFT;								// TFT CD key
	string m_CountryAbbrev;							// country abbreviation
	string m_Country;								// country
	uint32_t m_LocaleID;							// see: http://msdn.microsoft.com/en-us/library/0h88fahh%28VS.85%29.aspx
	string m_UserName;								// battle.net username
	string m_UserPassword;							// battle.net password
	string m_FirstChannel;							// the first chat channel to join upon entering chat (note: we hijack this to store the last channel when entering a game)
	string m_CurrentChannel;						// the current chat channel
	string m_RootAdmin;								// the root admin
	char m_CommandTrigger;							// the character prefix to identify commands
	unsigned char m_War3Version;					// custom warcraft 3 version for PvPGN users
	BYTEARRAY m_EXEVersion;							// custom exe version for PvPGN users
	BYTEARRAY m_EXEVersionHash;						// custom exe version hash for PvPGN users
	string m_PasswordHashType;						// password hash type for PvPGN users
	string m_PVPGNRealmName;						// realm name for PvPGN users (for mutual friend spoofchecks)
	uint32_t m_MaxMessageLength;					// maximum message length for PvPGN users
	uint32_t m_HostCounterID;						// the host counter ID to identify players from this realm
	uint32_t m_LastDisconnectedTime;				// GetTime when we were last disconnected from battle.net
	uint32_t m_LastConnectionAttemptTime;			// GetTime when we last attempted to connect to battle.net
	uint32_t m_LastNullTime;						// GetTime when the last null packet was sent for detecting disconnects
	uint32_t m_LastOutPacketTicks;					// GetTicks when the last packet was sent for the m_OutPackets queue
	uint32_t m_LastOutPacketSize;
	uint32_t m_FrequencyDelayTimes;
	uint32_t m_LastAdminRefreshTime;				// GetTime when the admin list was last refreshed from the database
	uint32_t m_LastBanRefreshTime;					// GetTime when the ban list was last refreshed from the database
	bool m_FirstConnect;							// if we haven't tried to connect to battle.net yet
	bool m_WaitingToConnect;						// if we're waiting to reconnect to battle.net after being disconnected
	bool m_LoggedIn;								// if we've logged into battle.net or not
	bool m_InChat;									// if we've entered chat or not (but we're not necessarily in a chat channel yet)
	bool m_HoldFriends;								// whether to auto hold friends when creating a game or not
	bool m_HoldClan;								// whether to auto hold clan members when creating a game or not
	bool m_PublicCommands;							// whether to allow public commands or not
	uint32_t m_LastRegisterProcess;
	uint32_t m_LastLogUpdateTime;
	//string m_AdminLog;
	string gCDLog;
	string gHACKLog;
	uint32_t m_LastXMLFileCreation;
	string gXML;
	string m_XMLGames;
	bool m_XMLUpdate;
	bool m_ForcedInfo;
	boost::mutex StatsUpdateMutex;
	bool b_StatsUpdate;

public:
	CBNET( CGHost *nGHost, string nServer, string nServerAlias, string nBNLSServer, uint16_t nBNLSPort, uint32_t nBNLSWardenCookie, string nCDKeyROC, string nCDKeyTFT, string nCountryAbbrev, string nCountry, uint32_t nLocaleID, string nUserName, string nUserPassword, string nFirstChannel, char nCommandTrigger, bool nHoldFriends, bool nHoldClan, bool nPublicCommands, unsigned char nWar3Version, BYTEARRAY nEXEVersion, BYTEARRAY nEXEVersionHash, string nPasswordHashType, string nPVPGNRealmName, uint32_t nMaxMessageLength, uint32_t nHostCounterID );
	~CBNET( );

	vector<string> m_Permissions;
	vector<string> m_AdminLog;

	bool GetExiting( )					{ return m_Exiting; }
	string GetServer( )					{ return m_Server; }
	string GetServerAlias( )			{ return m_ServerAlias; }
	string GetCDKeyROC( )				{ return m_CDKeyROC; }
	string GetCDKeyTFT( )				{ return m_CDKeyTFT; }
	string GetUserName( )				{ return m_UserName; }
	string GetUserPassword( )			{ return m_UserPassword; }
	string GetFirstChannel( )			{ return m_FirstChannel; }
	string GetCurrentChannel( )			{ return m_CurrentChannel; }
	string GetRootAdmin( )				{ return m_RootAdmin; }
	char GetCommandTrigger( )			{ return m_CommandTrigger; }
	BYTEARRAY GetEXEVersion( )			{ return m_EXEVersion; }
	BYTEARRAY GetEXEVersionHash( )		{ return m_EXEVersionHash; }
	string GetPasswordHashType( )		{ return m_PasswordHashType; }
	string GetPVPGNRealmName( )			{ return m_PVPGNRealmName; }
	uint32_t GetHostCounterID( )		{ return m_HostCounterID; }
	bool GetLoggedIn( )					{ return m_LoggedIn; }
	bool GetInChat( )					{ return m_InChat; }
	bool GetHoldFriends( )				{ return m_HoldFriends; }
	bool GetHoldClan( )					{ return m_HoldClan; }
	bool GetPublicCommands( )			{ return m_PublicCommands; }
	uint32_t GetOutPacketsQueued( )		{ return m_OutPackets.size( ); }
	BYTEARRAY GetUniqueName( );

	// processing functions

	unsigned int SetFD( void *fd, void *send_fd, int *nfds );
	bool Update( void *fd, void *send_fd );
	void ExtractPackets( );
	void ProcessPackets( );
	void ProcessChatEvent( CIncomingChatEvent *chatEvent );
	void BotCommand( string Message, string User, bool Whisper, bool ForceRoot );

	// functions to send packets to battle.net

	void SendJoinChannel( string channel );
	void SendGetFriendsList( );
	void SendGetClanList( );
	void QueueEnterChat( );
	void QueueChatCommand( string chatCommand );
	void QueueChatCommand( string chatCommand, string user, bool whisper );
	void QueueGameCreate( unsigned char state, string gameName, string hostName, CMap *map, CSaveGame *saveGame, uint32_t hostCounter );
	void QueueGameRefresh( unsigned char state, string gameName, string hostName, CMap *map, CSaveGame *saveGame, uint32_t upTime, uint32_t hostCounter );
	void QueueGameUncreate( );

	void UnqueuePackets( unsigned char type );
	void UnqueueChatCommand( string chatCommand );
	void UnqueueGameRefreshes( );

	// other functions

	uint32_t IsLevel( string name );
	string GetLevelName ( uint32_t level );
        bool IsRootAdmin( string name );
	CDBBan *IsBannedName( string name );
	CDBBan *IsBannedIP( string ip );
	void AddBan( string name, string ip, string gamename, string admin, string reason );
	void RemoveBan( string name );
	void HoldFriends( CBaseGame *game );
	void HoldClan( CBaseGame *game );
	void CD_Print( string message );
	void Hack_Print( string message );
	//void CreateXMLFile( uint32_t botid, string gamename,  string mappath, BYTEARRAY crc, BYTEARRAY flag, const char version, string ip, uint16_t hostport, uint32_t entrykey, uint32_t hostcounter, uint32_t height, uint32_t width );
};

#endif
