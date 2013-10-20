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

#ifndef STATSDOTA_H
#define STATSDOTA_H

//
// CStatsDOTA
//

class CDBDotAPlayer;

class CStatsDOTA : public CStats
{
private:
	CDBDotAPlayer *m_Players[12];
	uint32_t m_Winner;
	uint32_t m_Min;
	uint32_t m_Sec;

        // Custom
        uint32_t m_TowerLimit; // win condition on number of towers destroyed
        uint32_t m_KillLimit; // win condition on number of kills
        uint32_t m_TimeLimit; // time limit win condition; winner is more kills, or if even then higher (creep kills + creep denies) value
        uint32_t m_SentinelTowers;
        uint32_t m_ScourgeTowers;
        uint32_t m_SentinelKills;
        uint32_t m_ScourgeKills;
        uint32_t m_LastCreepTime; // last time we received creep stats, for the time limit win condition
        string victim;
        string killer;
	bool m_FirstBlood;
	uint32_t m_LeaverKills[12];
	uint32_t m_LeaverDeaths[12];
	uint32_t m_AssistsOnLeaverKills[12];
	uint32_t m_DeathsByLeaver[12];

public:
	CStatsDOTA( CBaseGame *nGame );
	virtual ~CStatsDOTA( );

	virtual bool ProcessAction( CIncomingAction *Action );
	virtual void Save( CGHost *GHost, CGHostDB *DB, uint32_t GameID );
	virtual void SetWinner( uint32_t nWinner ) { m_Winner = nWinner + 1; }
};

#endif
