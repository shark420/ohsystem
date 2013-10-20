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

#ifndef GAME_H
#define GAME_H

//
// CGame
//

class CDBBan;
class CDBGame;
class CDBGamePlayer;
class CDBInbox;
class CStats;
class CCallableBanCheck;
class CCallablePassCheck;
class CCallableGameAdd;
class CCallableGamePlayerSummaryCheck;
class CCallableStatsPlayerSummaryCheck;
class CCallableInboxSummaryCheck;
//class CCallablePlayerSummaryCheck;
class CCallableStatsSystem;

typedef pair<string,CCallableBanCheck *> PairedBanCheck;
typedef pair<string,CCallablePassCheck *> PairedPassCheck;
typedef pair<string,CCallableStatsPlayerSummaryCheck *> PairedGSCheck;
typedef pair<string,CCallableStatsPlayerSummaryCheck *> PairedRankCheck;
typedef pair<string,CCallableStatsPlayerSummaryCheck *> PairedStreakCheck;
typedef pair<string,CCallableInboxSummaryCheck *> PairedINCheck;
typedef pair<string,CCallableStatsPlayerSummaryCheck *> PairedSCheck;
typedef pair<string,CCallableStatsSystem *> PairedSS;

class CGame : public CBaseGame
{
protected:
	CDBBan *m_DBBanLast;						// last ban for the !banlast command - this is a pointer to one of the items in m_DBBans
	vector<CDBBan *> m_DBBans;					// vector of potential ban data for the database (see the Update function for more info, it's not as straightforward as you might think)
	CDBGame *m_DBGame;							// potential game data for the database
	vector<CDBGamePlayer *> m_DBGamePlayers;	// vector of potential gameplayer data for the database
	CStats *m_Stats;							// class to keep track of game stats such as kills/deaths/assists in dota
	CCallableGameAdd *m_CallableGameAdd;		// threaded database game addition in progress
	vector<PairedBanCheck> m_PairedBanChecks;	// vector of paired threaded database ban checks in progress
        vector<PairedPassCheck> m_PairedPassChecks;       // vector of paired threaded database password checks in progress
	vector<PairedGSCheck> m_PairedGSChecks;	// vector of paired threaded database game player summary checks in progress
        vector<PairedRankCheck> m_PairedRankChecks;
        vector<PairedINCheck> m_PairedINChecks;       // vector of paired threaded database ingame checks in progress
        vector<PairedStreakCheck> m_PairedStreakChecks;       // vector of paired threaded database ingame checks in progress
	vector<PairedSCheck> m_PairedSChecks;	// vector of paired threaded database DotA player summary checks in progress
        vector<PairedSS> m_PairedSSs;
	vector<string> m_AutoBans;
	bool m_EarlyDraw;
	bool IsAutoBanned( string name );
	uint32_t m_ForfeitTime;						// time that players forfeited, or 0 if not forfeited
	uint32_t m_ForfeitTeam;						// id of team that forfeited

public:
	CGame( CGHost *nGHost, CMap *nMap, CSaveGame *nSaveGame, uint16_t nHostPort, unsigned char nGameState, string nGameName, string nOwnerName, string nCreatorName, string nCreatorServer, uint32_t nGameType );
	virtual ~CGame( );

	virtual bool Update( void *fd, void *send_fd );
	virtual void EventPlayerDeleted( CGamePlayer *player );
	virtual bool EventPlayerAction( CGamePlayer *player, CIncomingAction *action );
	virtual bool EventPlayerBotCommand( CGamePlayer *player, string command, string payload );
	virtual void EventGameStarted( );
	virtual bool IsGameDataSaved( );
	virtual void SaveGameData( );
	virtual bool CustomVoteKickReason( string reason );
};

#endif
