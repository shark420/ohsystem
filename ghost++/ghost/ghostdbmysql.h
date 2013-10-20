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

#ifdef GHOST_MYSQL

#ifndef GHOSTDBMYSQL_H
#define GHOSTDBMYSQL_H

/**************
 *** SCHEMA ***
 **************

CREATE TABLE bans (
	id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
	botid INT NOT NULL,
	server VARCHAR(100) NOT NULL,
	name VARCHAR(15) NOT NULL,
	ip VARCHAR(15) NOT NULL,
	date DATETIME NOT NULL,
	gamename VARCHAR(31) NOT NULL,
	admin VARCHAR(15) NOT NULL,
	reason VARCHAR(255) NOT NULL,
	l_lefttime VARCHAR(15) NOT NULL,
	l_reason VARCHAR(255) NOT NULL,
	l_gameid INT NOT NULL
)

CREATE TABLE games (
	id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
	botid INT NOT NULL,
	server VARCHAR(100) NOT NULL,
	map VARCHAR(100) NOT NULL,
	datetime DATETIME NOT NULL,
	gamename VARCHAR(31) NOT NULL,
	ownername VARCHAR(15) NOT NULL,
	duration INT NOT NULL,
	gamestate INT NOT NULL,
	creatorname VARCHAR(15) NOT NULL,
	creatorserver VARCHAR(100) NOT NULL
)

CREATE TABLE gameplayers (
	id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
	botid INT NOT NULL,
	gameid INT NOT NULL,
	name VARCHAR(15) NOT NULL,
	ip VARCHAR(15) NOT NULL,
	spoofed INT NOT NULL,
	reserved INT NOT NULL,
	loadingtime INT NOT NULL,
	`left` INT NOT NULL,
	leftreason VARCHAR(100) NOT NULL,
	team INT NOT NULL,
	colour INT NOT NULL,
	spoofedrealm VARCHAR(100) NOT NULL,
	INDEX( gameid )
)

CREATE TABLE dotagames (
	id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
	botid INT NOT NULL,
	gameid INT NOT NULL,
	winner INT NOT NULL,
	min INT NOT NULL,
	sec INT NOT NULL
)

CREATE TABLE dotaplayers (
	id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
	botid INT NOT NULL,
	gameid INT NOT NULL,
	colour INT NOT NULL,
	kills INT NOT NULL,
	deaths INT NOT NULL,
	creepkills INT NOT NULL,
	creepdenies INT NOT NULL,
	assists INT NOT NULL,
	gold INT NOT NULL,
	neutralkills INT NOT NULL,
	item1 CHAR(4) NOT NULL,
	item2 CHAR(4) NOT NULL,
	item3 CHAR(4) NOT NULL,
	item4 CHAR(4) NOT NULL,
	item5 CHAR(4) NOT NULL,
	item6 CHAR(4) NOT NULL,
	hero CHAR(4) NOT NULL,
	newcolour INT NOT NULL,
	towerkills INT NOT NULL,
	raxkills INT NOT NULL,
	courierkills INT NOT NULL,
	INDEX( gameid, colour )
)

CREATE TABLE downloads (
	id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
	botid INT NOT NULL,
	map VARCHAR(100) NOT NULL,
	mapsize INT NOT NULL,
	datetime DATETIME NOT NULL,
	name VARCHAR(15) NOT NULL,
	ip VARCHAR(15) NOT NULL,
	spoofed INT NOT NULL,
	spoofedrealm VARCHAR(100) NOT NULL,
	downloadtime INT NOT NULL
)

CREATE TABLE scores (
	id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
	category VARCHAR(25) NOT NULL,
	name VARCHAR(15) NOT NULL,
	server VARCHAR(100) NOT NULL,
	score REAL NOT NULL
)

CREATE TABLE w3mmdplayers (
	id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
	botid INT NOT NULL,
	category VARCHAR(25) NOT NULL,
	gameid INT NOT NULL,
	pid INT NOT NULL,
	name VARCHAR(15) NOT NULL,
	flag VARCHAR(32) NOT NULL,
	leaver INT NOT NULL,
	practicing INT NOT NULL
)

CREATE TABLE w3mmdvars (
	id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
	botid INT NOT NULL,
	gameid INT NOT NULL,
	pid INT NOT NULL,
	varname VARCHAR(25) NOT NULL,
	value_int INT DEFAULT NULL,
	value_real REAL DEFAULT NULL,
	value_string VARCHAR(100) DEFAULT NULL
)

 **************
 *** SCHEMA ***
 **************/

//
// CGHostDBMySQL
//

class CGHostDBMySQL : public CGHostDB
{
private:
	string m_Server;
	string m_Database;
	string m_User;
	string m_Password;
	uint16_t m_Port;
	uint32_t m_BotID;
	queue<void *> m_IdleConnections;
	uint32_t m_NumConnections;
	uint32_t m_OutstandingCallables;
	vector<string> m_Name;

public:
	CGHostDBMySQL( CConfig *CFG );
	virtual ~CGHostDBMySQL( );

	virtual string GetStatus( );

	virtual void RecoverCallable( CBaseCallable *callable );

	// threaded database functions

	virtual void CreateThread( CBaseCallable *callable );
        virtual CCallableFromCheck *ThreadedFromCheck( string ip );
        virtual CCallableRegAdd *ThreadedRegAdd( string user, string server, string mail, string password, string type );
	virtual CCallableStatsSystem *ThreadedStatsSystem( string user, string input, uint32_t one, string type );
        virtual CCallablePWCheck *ThreadedPWCheck( string user );
        virtual CCallablePassCheck *ThreadedPassCheck( string user, string pass, uint32_t st );
	virtual CCallablepm *Threadedpm( string user, string listener, uint32_t status, string message, string type );
        virtual CCallablePList *ThreadedPList( string server );
        virtual CCallableFlameList *ThreadedFlameList( );
	virtual CCallableAnnounceList *ThreadedAnnounceList( );
        virtual CCallableDCountryList *ThreadedDCountryList( );
        virtual CCallableStoreLog *ThreadedStoreLog( uint32_t chatid, string game, vector<string> admin );
        virtual CCallablegs *Threadedgs( uint32_t chatid, string gn, uint32_t st, uint32_t gametype );
        virtual CCallablepenp *Threadedpenp( string name, string reason, string admin, uint32_t amount, string type );
	virtual CCallableBanCount *ThreadedBanCount( string server );
	virtual CCallableBanCheck *ThreadedBanCheck( string server, string user, string ip );
        virtual CCallableBanCheck2 *ThreadedBanCheck2( string server, string user, string type );
	virtual CCallableBanAdd *ThreadedBanAdd( string server, string user, string ip, string gamename, string admin, string reason, uint32_t bantime, string country );
	virtual CCallablePUp *ThreadedPUp( string name, uint32_t level, string realm, string user );
	virtual CCallableBanRemove *ThreadedBanRemove( string server, string user );
        virtual CCallableTBRemove *ThreadedTBRemove( string server );
	virtual CCallableBanRemove *ThreadedBanRemove( string user );
	virtual CCallableBanList *ThreadedBanList( string server );
	virtual CCallableCommandList *ThreadedCommandList(  );
	virtual CCallableClean *ThreadedClean( );
	virtual CCallableGameAdd *ThreadedGameAdd( string server, string map, string gamename, string ownername, uint32_t duration, uint32_t gamestate, string creatorname, string creatorserver, uint32_t gametype, vector<string> lobbylog, vector<string> gamelog );
	virtual CCallableGameUpdate *ThreadedGameUpdate( string map, string gamename, string ownername, string creatorname, uint32_t players, string usernames, uint32_t slotsTotal, uint32_t totalGames, uint32_t totalPlayers, bool add );
	virtual CCallableGamePlayerAdd *ThreadedGamePlayerAdd( uint32_t gameid, string name, string ip, uint32_t spoofed, string spoofedrealm, uint32_t reserved, uint32_t loadingtime, uint32_t left, string leftreason, uint32_t team, uint32_t colour );
	virtual CCallableGamePlayerSummaryCheck *ThreadedGamePlayerSummaryCheck( string name );
        virtual CCallableStatsPlayerSummaryCheck *ThreadedStatsPlayerSummaryCheck( string name );
        virtual CCallableInboxSummaryCheck *ThreadedInboxSummaryCheck( string name );
	virtual CCallableDotAGameAdd *ThreadedDotAGameAdd( uint32_t gameid, uint32_t winner, uint32_t min, uint32_t sec );
	virtual CCallableDotAPlayerAdd *ThreadedDotAPlayerAdd( uint32_t gameid, uint32_t colour, uint32_t kills, uint32_t deaths, uint32_t creepkills, uint32_t creepdenies, uint32_t assists, uint32_t gold, uint32_t neutralkills, string item1, string item2, string item3, string item4, string item5, string item6, string hero, uint32_t newcolour, uint32_t towerkills, uint32_t raxkills, uint32_t courierkills, uint32_t level );
	virtual CCallableDotAPlayerSummaryCheck *ThreadedDotAPlayerSummaryCheck( string name );
	virtual CCallableDownloadAdd *ThreadedDownloadAdd( string map, uint32_t mapsize, string name, string ip, uint32_t spoofed, string spoofedrealm, uint32_t downloadtime );
	virtual CCallableScoreCheck *ThreadedScoreCheck( string category, string name, string server );
	virtual CCallableW3MMDPlayerAdd *ThreadedW3MMDPlayerAdd( string category, uint32_t gameid, uint32_t pid, string name, string flag, uint32_t leaver, uint32_t practicing );
	virtual CCallableW3MMDVarAdd *ThreadedW3MMDVarAdd( uint32_t gameid, map<VarP,int32_t> var_ints );
	virtual CCallableW3MMDVarAdd *ThreadedW3MMDVarAdd( uint32_t gameid, map<VarP,double> var_reals );
	virtual CCallableW3MMDVarAdd *ThreadedW3MMDVarAdd( uint32_t gameid, map<VarP,string> var_strings );

	// other database functions

	virtual void *GetIdleConnection( );
};

//
// global helper functions
//

string MySQLFromCheck( void *conn, string *error, uint32_t botid, string ip  );
uint32_t MySQLRegAdd( void *conn, string *error, uint32_t botid, string user, string server, string mail, string password, string type );
string MySQLStatsSystem( void *conn, string *error, uint32_t botid, string user, string input, uint32_t one, string type );
uint32_t MySQLPWCheck( void *conn, string *error, uint32_t botid, string user );
uint32_t MySQLPassCheck( void *conn, string *error, uint32_t botid, string user, string pass, uint32_t st );
uint32_t MySQLpm( void *conn, string *error, uint32_t botid, string user, string listener, uint32_t status, string message, string type );
vector<string> MySQLPList( void *conn, string *error, uint32_t botid, string server );
vector<string> MySQLFlameList( void *conn, string *error, uint32_t botid );
vector<string> MySQLAnnounceList( void *conn, string *error, uint32_t botid );
vector<string> MySQLDCountryList( void *conn, string *error, uint32_t botid );
uint32_t MySQLStoreLog( void *conn, string *error, uint32_t botid, uint32_t chatid, string game, vector<string> admin );
uint32_t MySQLgs( void *conn, string *error, uint32_t botid, uint32_t chatid, string gn, uint32_t st, uint32_t gametype );
uint32_t MySQLpenp( void *conn, string *error, uint32_t botid, string name, string reason, string admin, uint32_t amount, string type );
uint32_t MySQLBanCount( void *conn, string *error, uint32_t botid, string server );
CDBBan *MySQLBanCheck( void *conn, string *error, uint32_t botid, string server, string user, string ip );
string MySQLBanCheck2( void *conn, string *error, uint32_t botid, string server, string user, string type );
string MySQLBanAdd( void *conn, string *error, uint32_t botid, string server, string user, string ip, string gamename, string admin, string reason, uint32_t bantime, string country );
bool MySQLPUp( void *conn, string *error, uint32_t botid, string name, uint32_t level, string realm, string user );
bool MySQLBanRemove( void *conn, string *error, uint32_t botid, string server, string user );
bool MySQLTBRemove( void *conn, string *error, uint32_t botid, string server );
bool MySQLBanRemove( void *conn, string *error, uint32_t botid, string user );
vector<CDBBan *> MySQLBanList( void *conn, string *error, uint32_t botid, string server );
vector<string> MySQLCommandList( void *conn, string *error, uint32_t botid );
bool MySQLClean( void *conn, string *error, uint32_t botid );
uint32_t MySQLGameAdd( void *conn, string *error, uint32_t botid, string server, string map, string gamename, string ownername, uint32_t duration, uint32_t gamestate, string creatorname, string creatorserver, uint32_t gametype, vector<string> lobbylog, vector<string> gamelog );
string MySQLGameUpdate( void *conn, string *error, uint32_t botid, string map, string gamename, string ownername, string creatorname, uint32_t players, string usernames, uint32_t slotsTotal, uint32_t totalGames, uint32_t totalPlayers, bool add );
uint32_t MySQLGamePlayerAdd( void *conn, string *error, uint32_t botid, uint32_t gameid, string name, string ip, uint32_t spoofed, string spoofedrealm, uint32_t reserved, uint32_t loadingtime, uint32_t left, string leftreason, uint32_t team, uint32_t colour );
CDBGamePlayerSummary *MySQLGamePlayerSummaryCheck( void *conn, string *error, uint32_t botid, string name );
CDBStatsPlayerSummary *MySQLStatsPlayerSummaryCheck( void *conn, string *error, uint32_t botid, string name );
CDBInboxSummary *MySQLInboxSummaryCheck( void *conn, string *error, uint32_t botid, string name );
uint32_t MySQLDotAGameAdd( void *conn, string *error, uint32_t botid, uint32_t gameid, uint32_t winner, uint32_t min, uint32_t sec );
uint32_t MySQLDotAPlayerAdd( void *conn, string *error, uint32_t botid, uint32_t gameid, uint32_t colour, uint32_t kills, uint32_t deaths, uint32_t creepkills, uint32_t creepdenies, uint32_t assists, uint32_t gold, uint32_t neutralkills, string item1, string item2, string item3, string item4, string item5, string item6, string hero, uint32_t newcolour, uint32_t towerkills, uint32_t raxkills, uint32_t courierkills, uint32_t level );
CDBDotAPlayerSummary *MySQLDotAPlayerSummaryCheck( void *conn, string *error, uint32_t botid, string name );
bool MySQLDownloadAdd( void *conn, string *error, uint32_t botid, string map, uint32_t mapsize, string name, string ip, uint32_t spoofed, string spoofedrealm, uint32_t downloadtime );
double MySQLScoreCheck( void *conn, string *error, uint32_t botid, string category, string name, string server );
uint32_t MySQLW3MMDPlayerAdd( void *conn, string *error, uint32_t botid, string category, uint32_t gameid, uint32_t pid, string name, string flag, uint32_t leaver, uint32_t practicing );
bool MySQLW3MMDVarAdd( void *conn, string *error, uint32_t botid, uint32_t gameid, map<VarP,int32_t> var_ints );
bool MySQLW3MMDVarAdd( void *conn, string *error, uint32_t botid, uint32_t gameid, map<VarP,double> var_reals );
bool MySQLW3MMDVarAdd( void *conn, string *error, uint32_t botid, uint32_t gameid, map<VarP,string> var_strings );

//
// MySQL Callables
//

class CMySQLCallable : virtual public CBaseCallable
{
protected:
	void *m_Connection;
	string m_SQLServer;
	string m_SQLDatabase;
	string m_SQLUser;
	string m_SQLPassword;
	uint16_t m_SQLPort;
	uint32_t m_SQLBotID;

public:
	CMySQLCallable( void *nConnection, uint32_t nSQLBotID, string nSQLServer, string nSQLDatabase, string nSQLUser, string nSQLPassword, uint16_t nSQLPort ) : CBaseCallable( ), m_Connection( nConnection ), m_SQLBotID( nSQLBotID ), m_SQLServer( nSQLServer ), m_SQLDatabase( nSQLDatabase ), m_SQLUser( nSQLUser ), m_SQLPassword( nSQLPassword ), m_SQLPort( nSQLPort ) { }
	virtual ~CMySQLCallable( ) { }

	virtual void *GetConnection( )	{ return m_Connection; }

	virtual void Init( );
	virtual void Close( );
};

class CMySQLCallableFromCheck : public CCallableFromCheck, public CMySQLCallable
{
public:
        CMySQLCallableFromCheck( string nIP, void *nConnection, uint32_t nSQLBotID, string nSQLServer, string nSQLDatabase, string nSQLUser, string nSQLPassword, uint16_t nSQLPort ) : CBaseCallable( ), CCallableFromCheck( nIP ), CMySQLCallable( nConnection, nSQLBotID, nSQLServer, nSQLDatabase, nSQLUser, nSQLPassword, nSQLPort ) { }
        virtual ~CMySQLCallableFromCheck( ) { }

        virtual void operator( )( );
        virtual void Init( ) { CMySQLCallable :: Init( ); }
        virtual void Close( ) { CMySQLCallable :: Close( ); }
};

class CMySQLCallableRegAdd : public CCallableRegAdd, public CMySQLCallable
{
public:
        CMySQLCallableRegAdd( string nUser, string nServer, string nMail, string nPassword, string nType, void *nConnection, uint32_t nSQLBotID, string nSQLServer, string nSQLDatabase, string nSQLUser, string nSQLPassword, uint16_t nSQLPort ) : CBaseCallable( ), CCallableRegAdd( nUser, nServer, nMail, nPassword, nType ), CMySQLCallable( nConnection, nSQLBotID, nSQLServer, nSQLDatabase, nSQLUser, nSQLPassword, nSQLPort ) { }
        virtual ~CMySQLCallableRegAdd( ) { }

        virtual void operator( )( );
        virtual void Init( ) { CMySQLCallable :: Init( ); }
        virtual void Close( ) { CMySQLCallable :: Close( ); }
};

class CMySQLCallableStatsSystem : public CCallableStatsSystem, public CMySQLCallable
{
public:
        CMySQLCallableStatsSystem( string nUser, string nInput, uint32_t nOne, string nType, void *nConnection, uint32_t nSQLBotID, string nSQLServer, string nSQLDatabase, string nSQLUser, string nSQLPassword, uint16_t nSQLPort ) : CBaseCallable( ), CCallableStatsSystem( nUser, nInput, nOne, nType ), CMySQLCallable( nConnection, nSQLBotID, nSQLServer, nSQLDatabase, nSQLUser, nSQLPassword, nSQLPort ) { }
        virtual ~CMySQLCallableStatsSystem( ) { }

        virtual void operator( )( );
        virtual void Init( ) { CMySQLCallable :: Init( ); }
        virtual void Close( ) { CMySQLCallable :: Close( ); }
};

class CMySQLCallablePWCheck : public CCallablePWCheck, public CMySQLCallable
{
public:
        CMySQLCallablePWCheck( string nUser, void *nConnection, uint32_t nSQLBotID, string nSQLServer, string nSQLDatabase, string nSQLUser, string nSQLPassword, uint16_t nSQLPort ) : CBaseCallable( ), CCallablePWCheck( nUser ), CMySQLCallable( nConnection, nSQLBotID, nSQLServer, nSQLDatabase, nSQLUser, nSQLPassword, nSQLPort ) { }
        virtual ~CMySQLCallablePWCheck( ) { }

        virtual void operator( )( );
        virtual void Init( ) { CMySQLCallable :: Init( ); }
        virtual void Close( ) { CMySQLCallable :: Close( ); }
};

class CMySQLCallablePassCheck : public CCallablePassCheck, public CMySQLCallable
{
public:
        CMySQLCallablePassCheck( string nUser, string nPass, uint32_t nST, void *nConnection, uint32_t nSQLBotID, string nSQLServer, string nSQLDatabase, string nSQLUser, string nSQLPassword, uint16_t nSQLPort ) : CBaseCallable( ), CCallablePassCheck( nUser, nPass, nST ), CMySQLCallable( nConnection, nSQLBotID, nSQLServer, nSQLDatabase, nSQLUser, nSQLPassword, nSQLPort  ) { }
        virtual ~CMySQLCallablePassCheck( ) { }

        virtual void operator( )( );
        virtual void Init( ) { CMySQLCallable :: Init( ); }
        virtual void Close( ) { CMySQLCallable :: Close( ); }
};

class CMySQLCallablepm : public CCallablepm, public CMySQLCallable
{
public:
        CMySQLCallablepm( string nUser, string nListener, uint32_t nStatus, string nMessage, string nType, void *nConnection, uint32_t nSQLBotID, string nSQLServer, string nSQLDatabase, string nSQLUser, string nSQLPassword, uint16_t nSQLPort ) : CBaseCallable( ), CCallablepm( nUser, nListener, nStatus, nMessage, nType ), CMySQLCallable( nConnection, nSQLBotID, nSQLServer, nSQLDatabase, nSQLUser, nSQLPassword, nSQLPort  ) { }
        virtual ~CMySQLCallablepm( ) { }

        virtual void operator( )( );
        virtual void Init( ) { CMySQLCallable :: Init( ); }
        virtual void Close( ) { CMySQLCallable :: Close( ); }
};

class CMySQLCallablePList : public CCallablePList, public CMySQLCallable
{
public:
        CMySQLCallablePList( string nServer, void *nConnection, uint32_t nSQLBotID, string nSQLServer, string nSQLDatabase, string nSQLUser, string nSQLPassword, uint16_t nSQLPort ) : CBaseCallable( ), CCallablePList( nServer ), CMySQLCallable( nConnection, nSQLBotID, nSQLServer, nSQLDatabase, nSQLUser, nSQLPassword, nSQLPort ) { }
        virtual ~CMySQLCallablePList( ) { }

        virtual void operator( )( );
        virtual void Init( ) { CMySQLCallable :: Init( ); }
        virtual void Close( ) { CMySQLCallable :: Close( ); }
};

class CMySQLCallableFlameList : public CCallableFlameList, public CMySQLCallable
{
public:
        CMySQLCallableFlameList( void *nConnection, uint32_t nSQLBotID, string nSQLServer, string nSQLDatabase, string nSQLUser, string nSQLPassword, uint16_t nSQLPort ) : CBaseCallable( ), CCallableFlameList( ), CMySQLCallable( nConnection, nSQLBotID, nSQLServer, nSQLDatabase, nSQLUser, nSQLPassword, nSQLPort ) { }
        virtual ~CMySQLCallableFlameList( ) { }

        virtual void operator( )( );
        virtual void Init( ) { CMySQLCallable :: Init( ); }
        virtual void Close( ) { CMySQLCallable :: Close( ); }
};

class CMySQLCallableAnnounceList : public CCallableAnnounceList, public CMySQLCallable
{
public:
        CMySQLCallableAnnounceList( void *nConnection, uint32_t nSQLBotID, string nSQLServer, string nSQLDatabase, string nSQLUser, string nSQLPassword, uint16_t nSQLPort ) : CBaseCallable( ), CCallableAnnounceList( ), CMySQLCallable( nConnection, nSQLBotID, nSQLServer, nSQLDatabase, nSQLUser, nSQLPassword, nSQLPort ) { }
        virtual ~CMySQLCallableAnnounceList( ) { }

        virtual void operator( )( );
        virtual void Init( ) { CMySQLCallable :: Init( ); }
        virtual void Close( ) { CMySQLCallable :: Close( ); }
};

class CMySQLCallableDCountryList : public CCallableDCountryList, public CMySQLCallable
{
public:
        CMySQLCallableDCountryList( void *nConnection, uint32_t nSQLBotID, string nSQLServer, string nSQLDatabase, string nSQLUser, string nSQLPassword, uint16_t nSQLPort ) : CBaseCallable( ), CCallableDCountryList( ), CMySQLCallable( nConnection, nSQLBotID, nSQLServer, nSQLDatabase, nSQLUser, nSQLPassword, nSQLPort ) { }
        virtual ~CMySQLCallableDCountryList( ) { }

        virtual void operator( )( );
        virtual void Init( ) { CMySQLCallable :: Init( ); }
        virtual void Close( ) { CMySQLCallable :: Close( ); }
};

class CMySQLCallableStoreLog : public CCallableStoreLog, public CMySQLCallable
{
public:
        CMySQLCallableStoreLog( uint32_t nChatID, string nGame, vector<string> nAdmin, void *nConnection, uint32_t nSQLBotID, string nSQLServer, string nSQLDatabase, string nSQLUser, string nSQLPassword, uint16_t nSQLPort ) : CBaseCallable( ), CCallableStoreLog( nChatID, nGame, nAdmin ), CMySQLCallable( nConnection, nSQLBotID, nSQLServer, nSQLDatabase, nSQLUser, nSQLPassword, nSQLPort ) { }
        virtual ~CMySQLCallableStoreLog( ) { }

        virtual void operator( )( );
        virtual void Init( ) { CMySQLCallable :: Init( ); }
        virtual void Close( ) { CMySQLCallable :: Close( ); }
};

class CMySQLCallablegs : public CCallablegs, public CMySQLCallable
{
public:
        CMySQLCallablegs( uint32_t nChatID, string nGN, uint32_t nST, uint32_t nGameType, void *nConnection, uint32_t nSQLBotID, string nSQLServer, string nSQLDatabase, string nSQLUser, string nSQLPassword, uint16_t nSQLPort ) : CBaseCallable( ), CCallablegs( nChatID, nGN, nST, nGameType ), CMySQLCallable( nConnection, nSQLBotID, nSQLServer, nSQLDatabase, nSQLUser, nSQLPassword, nSQLPort ) { }
        virtual ~CMySQLCallablegs( ) { }

        virtual void operator( )( );
        virtual void Init( ) { CMySQLCallable :: Init( ); }
        virtual void Close( ) { CMySQLCallable :: Close( ); }
};

class CMySQLCallablepenp : public CCallablepenp, public CMySQLCallable
{
public:
        CMySQLCallablepenp( string nName, string nReason, string nAdmin, uint32_t nAmount, string nType, void *nConnection, uint32_t nSQLBotID, string nSQLServer, string nSQLDatabase, string nSQLUser, string nSQLPassword, uint16_t nSQLPort ) : CBaseCallable( ), CCallablepenp( nName, nReason, nAdmin, nAmount, nType ), CMySQLCallable( nConnection, nSQLBotID, nSQLServer, nSQLDatabase, nSQLUser, nSQLPassword, nSQLPort ) { }
        virtual ~CMySQLCallablepenp( ) { }

        virtual void operator( )( );
        virtual void Init( ) { CMySQLCallable :: Init( ); }
        virtual void Close( ) { CMySQLCallable :: Close( ); }
};

class CMySQLCallableBanCount : public CCallableBanCount, public CMySQLCallable
{
public:
	CMySQLCallableBanCount( string nServer, void *nConnection, uint32_t nSQLBotID, string nSQLServer, string nSQLDatabase, string nSQLUser, string nSQLPassword, uint16_t nSQLPort ) : CBaseCallable( ), CCallableBanCount( nServer ), CMySQLCallable( nConnection, nSQLBotID, nSQLServer, nSQLDatabase, nSQLUser, nSQLPassword, nSQLPort ) { }
	virtual ~CMySQLCallableBanCount( ) { }

	virtual void operator( )( );
	virtual void Init( ) { CMySQLCallable :: Init( ); }
	virtual void Close( ) { CMySQLCallable :: Close( ); }
};

class CMySQLCallableBanCheck : public CCallableBanCheck, public CMySQLCallable
{
public:
	CMySQLCallableBanCheck( string nServer, string nUser, string nIP, void *nConnection, uint32_t nSQLBotID, string nSQLServer, string nSQLDatabase, string nSQLUser, string nSQLPassword, uint16_t nSQLPort ) : CBaseCallable( ), CCallableBanCheck( nServer, nUser, nIP ), CMySQLCallable( nConnection, nSQLBotID, nSQLServer, nSQLDatabase, nSQLUser, nSQLPassword, nSQLPort ) { }
	virtual ~CMySQLCallableBanCheck( ) { }

	virtual void operator( )( );
	virtual void Init( ) { CMySQLCallable :: Init( ); }
	virtual void Close( ) { CMySQLCallable :: Close( ); }
};

class CMySQLCallableBanCheck2 : public CCallableBanCheck2, public CMySQLCallable
{
public:
        CMySQLCallableBanCheck2( string nServer, string nUser, string nType, void *nConnection, uint32_t nSQLBotID, string nSQLServer, string nSQLDatabase, string nSQLUser, string nSQLPassword, uint16_t nSQLPort ) : CBaseCallable( ), CCallableBanCheck2( nServer, nUser, nType ), CMySQLCallable( nConnection, nSQLBotID, nSQLServer, nSQLDatabase, nSQLUser, nSQLPassword, nSQLPort ) { }
        virtual ~CMySQLCallableBanCheck2( ) { }

        virtual void operator( )( );
        virtual void Init( ) { CMySQLCallable :: Init( ); }
        virtual void Close( ) { CMySQLCallable :: Close( ); }
};


class CMySQLCallableBanAdd : public CCallableBanAdd, public CMySQLCallable
{
public:
	CMySQLCallableBanAdd( string nServer, string nUser, string nIP, string nGameName, string nAdmin, string nReason, uint32_t nBanTime, string nCountry, void *nConnection, uint32_t nSQLBotID, string nSQLServer, string nSQLDatabase, string nSQLUser, string nSQLPassword, uint16_t nSQLPort ) : CBaseCallable( ), CCallableBanAdd( nServer, nUser, nIP, nGameName, nAdmin, nReason, nBanTime, nCountry ), CMySQLCallable( nConnection, nSQLBotID, nSQLServer, nSQLDatabase, nSQLUser, nSQLPassword, nSQLPort ) { }
	virtual ~CMySQLCallableBanAdd( ) { }

	virtual void operator( )( );
	virtual void Init( ) { CMySQLCallable :: Init( ); }
	virtual void Close( ) { CMySQLCallable :: Close( ); }
};

class CMySQLCallablePUp : public CCallablePUp, public CMySQLCallable
{
public:
        CMySQLCallablePUp( string nName, uint32_t nLevel, string nRealm, string nUser, void *nConnection, uint32_t nSQLBotID, string nSQLServer, string nSQLDatabase, string nSQLUser, string nSQLPassword, uint16_t nSQLPort ) : CBaseCallable( ), CCallablePUp( nName, nLevel, nRealm, nUser ), CMySQLCallable( nConnection, nSQLBotID, nSQLServer, nSQLDatabase, nSQLUser, nSQLPassword, nSQLPort ) { }
        virtual ~CMySQLCallablePUp( ) { }

        virtual void operator( )( );
        virtual void Init( ) { CMySQLCallable :: Init( ); }
        virtual void Close( ) { CMySQLCallable :: Close( ); }
};

class CMySQLCallableBanRemove : public CCallableBanRemove, public CMySQLCallable
{
public:
	CMySQLCallableBanRemove( string nServer, string nUser, void *nConnection, uint32_t nSQLBotID, string nSQLServer, string nSQLDatabase, string nSQLUser, string nSQLPassword, uint16_t nSQLPort ) : CBaseCallable( ), CCallableBanRemove( nServer, nUser ), CMySQLCallable( nConnection, nSQLBotID, nSQLServer, nSQLDatabase, nSQLUser, nSQLPassword, nSQLPort ) { }
	virtual ~CMySQLCallableBanRemove( ) { }

	virtual void operator( )( );
	virtual void Init( ) { CMySQLCallable :: Init( ); }
	virtual void Close( ) { CMySQLCallable :: Close( ); }
};

class CMySQLCallableTBRemove : public CCallableTBRemove, public CMySQLCallable
{
public:
        CMySQLCallableTBRemove( string nServer, void *nConnection, uint32_t nSQLBotID, string nSQLServer, string nSQLDatabase, string nSQLUser, string nSQLPassword, uint16_t nSQLPort ) : CBaseCallable( ), CCallableTBRemove( nServer ), CMySQLCallable( nConnection, nSQLBotID, nSQLServer, nSQLDatabase, nSQLUser, nSQLPassword, nSQLPort ) { }
        virtual ~CMySQLCallableTBRemove( ) { }

        virtual void operator( )( );
        virtual void Init( ) { CMySQLCallable :: Init( ); }
        virtual void Close( ) { CMySQLCallable :: Close( ); }
};

class CMySQLCallableBanList : public CCallableBanList, public CMySQLCallable
{
public:
	CMySQLCallableBanList( string nServer, void *nConnection, uint32_t nSQLBotID, string nSQLServer, string nSQLDatabase, string nSQLUser, string nSQLPassword, uint16_t nSQLPort ) : CBaseCallable( ), CCallableBanList( nServer ), CMySQLCallable( nConnection, nSQLBotID, nSQLServer, nSQLDatabase, nSQLUser, nSQLPassword, nSQLPort ) { }
	virtual ~CMySQLCallableBanList( ) { }

	virtual void operator( )( );
	virtual void Init( ) { CMySQLCallable :: Init( ); }
	virtual void Close( ) { CMySQLCallable :: Close( ); }
};

class CMySQLCallableCommandList : public CCallableCommandList, public CMySQLCallable
{
public:
	CMySQLCallableCommandList( void *nConnection, uint32_t nSQLBotID, string nSQLServer, string nSQLDatabase, string nSQLUser, string nSQLPassword, uint16_t nSQLPort ) : CBaseCallable( ), CMySQLCallable( nConnection, nSQLBotID, nSQLServer, nSQLDatabase, nSQLUser, nSQLPassword, nSQLPort ) { }
	virtual ~CMySQLCallableCommandList( ) { }

	virtual void operator( )( );
	virtual void Init( ) { CMySQLCallable :: Init( ); }
	virtual void Close( ) { CMySQLCallable :: Close( ); }
};

class CMySQLCallableClean : public CCallableClean, public CMySQLCallable
{
public:
        CMySQLCallableClean( void *nConnection, uint32_t nSQLBotID, string nSQLServer, string nSQLDatabase, string nSQLUser, string nSQLPassword, uint16_t nSQLPort ) : CBaseCallable( ), CMySQLCallable( nConnection, nSQLBotID, nSQLServer, nSQLDatabase, nSQLUser, nSQLPassword, nSQLPort ) { }
        virtual ~CMySQLCallableClean( ) { }

        virtual void operator( )( );
        virtual void Init( ) { CMySQLCallable :: Init( ); }
        virtual void Close( ) { CMySQLCallable :: Close( ); }
};

class CMySQLCallableGameAdd : public CCallableGameAdd, public CMySQLCallable
{
public:
	CMySQLCallableGameAdd( string nServer, string nMap, string nGameName, string nOwnerName, uint32_t nDuration, uint32_t nGameState, string nCreatorName, string nCreatorServer, uint32_t nGameType, vector<string> nLobbyLog, vector<string> nGameLog, void *nConnection, uint32_t nSQLBotID, string nSQLServer, string nSQLDatabase, string nSQLUser, string nSQLPassword, uint16_t nSQLPort ) : CBaseCallable( ), CCallableGameAdd( nServer, nMap, nGameName, nOwnerName, nDuration, nGameState, nCreatorName, nCreatorServer, nGameType, nLobbyLog, nGameLog ), CMySQLCallable( nConnection, nSQLBotID, nSQLServer, nSQLDatabase, nSQLUser, nSQLPassword, nSQLPort ) { }
	virtual ~CMySQLCallableGameAdd( ) { }

	virtual void operator( )( );
	virtual void Init( ) { CMySQLCallable :: Init( ); }
	virtual void Close( ) { CMySQLCallable :: Close( ); }
};

class CMySQLCallableGameUpdate : public CCallableGameUpdate, public CMySQLCallable
{
public:
 CMySQLCallableGameUpdate( string map, string gamename, string ownername, string creatorname, uint32_t players, string usernames, uint32_t slotsTotal, uint32_t totalGames, uint32_t totalPlayers, bool add, void *nConnection, uint32_t nSQLBotID, string nSQLServer, string nSQLDatabase, string nSQLUser, string nSQLPassword, uint16_t nSQLPort ) : CBaseCallable( ), CCallableGameUpdate( map, gamename, ownername, creatorname, players, usernames, slotsTotal, totalGames, totalPlayers, add ), CMySQLCallable( nConnection, nSQLBotID, nSQLServer, nSQLDatabase, nSQLUser, nSQLPassword, nSQLPort ) { }
	virtual ~CMySQLCallableGameUpdate( ) { }

	virtual void operator( )( );
	virtual void Init( ) { CMySQLCallable :: Init( ); }
	virtual void Close( ) { CMySQLCallable :: Close( ); }
};

class CMySQLCallableGamePlayerAdd : public CCallableGamePlayerAdd, public CMySQLCallable
{
public:
	CMySQLCallableGamePlayerAdd( uint32_t nGameID, string nName, string nIP, uint32_t nSpoofed, string nSpoofedRealm, uint32_t nReserved, uint32_t nLoadingTime, uint32_t nLeft, string nLeftReason, uint32_t nTeam, uint32_t nColour, void *nConnection, uint32_t nSQLBotID, string nSQLServer, string nSQLDatabase, string nSQLUser, string nSQLPassword, uint16_t nSQLPort ) : CBaseCallable( ), CCallableGamePlayerAdd( nGameID, nName, nIP, nSpoofed, nSpoofedRealm, nReserved, nLoadingTime, nLeft, nLeftReason, nTeam, nColour ), CMySQLCallable( nConnection, nSQLBotID, nSQLServer, nSQLDatabase, nSQLUser, nSQLPassword, nSQLPort ) { }
	virtual ~CMySQLCallableGamePlayerAdd( ) { }

	virtual void operator( )( );
	virtual void Init( ) { CMySQLCallable :: Init( ); }
	virtual void Close( ) { CMySQLCallable :: Close( ); }
};

class CMySQLCallableGamePlayerSummaryCheck : public CCallableGamePlayerSummaryCheck, public CMySQLCallable
{
public:
	CMySQLCallableGamePlayerSummaryCheck( string nName, void *nConnection, uint32_t nSQLBotID, string nSQLServer, string nSQLDatabase, string nSQLUser, string nSQLPassword, uint16_t nSQLPort ) : CBaseCallable( ), CCallableGamePlayerSummaryCheck( nName ), CMySQLCallable( nConnection, nSQLBotID, nSQLServer, nSQLDatabase, nSQLUser, nSQLPassword, nSQLPort ) { }
	virtual ~CMySQLCallableGamePlayerSummaryCheck( ) { }

	virtual void operator( )( );
	virtual void Init( ) { CMySQLCallable :: Init( ); }
	virtual void Close( ) { CMySQLCallable :: Close( ); }
};

class CMySQLCallableStatsPlayerSummaryCheck : public CCallableStatsPlayerSummaryCheck, public CMySQLCallable
{
public:
        CMySQLCallableStatsPlayerSummaryCheck( string nName, void *nConnection, uint32_t nSQLBotID, string nSQLServer, string nSQLDatabase, string nSQLUser, string nSQLPassword, uint16_t nSQLPort ) : CBaseCallable( ), CCallableStatsPlayerSummaryCheck( nName ), CMySQLCallable( nConnection, nSQLBotID, nSQLServer, nSQLDatabase, nSQLUser, nSQLPassword, nSQLPort ) { }
        virtual ~CMySQLCallableStatsPlayerSummaryCheck( ) { }

        virtual void operator( )( );
        virtual void Init( ) { CMySQLCallable :: Init( ); }
        virtual void Close( ) { CMySQLCallable :: Close( ); }
};

class CMySQLCallableInboxSummaryCheck : public CCallableInboxSummaryCheck, public CMySQLCallable
{
public:
        CMySQLCallableInboxSummaryCheck( string nName, void *nConnection, uint32_t nSQLBotID, string nSQLServer, string nSQLDatabase, string nSQLUser, string nSQLPassword, uint16_t nSQLPort ) : CBaseCallable( ), CCallableInboxSummaryCheck( nName ), CMySQLCallable( nConnection, nSQLBotID, nSQLServer, nSQLDatabase, nSQLUser, nSQLPassword, nSQLPort ) { }
        virtual ~CMySQLCallableInboxSummaryCheck( ) { }

        virtual void operator( )( );
        virtual void Init( ) { CMySQLCallable :: Init( ); }
        virtual void Close( ) { CMySQLCallable :: Close( ); }
};

class CMySQLCallableDotAGameAdd : public CCallableDotAGameAdd, public CMySQLCallable
{
public:
	CMySQLCallableDotAGameAdd( uint32_t nGameID, uint32_t nWinner, uint32_t nMin, uint32_t nSec, void *nConnection, uint32_t nSQLBotID, string nSQLServer, string nSQLDatabase, string nSQLUser, string nSQLPassword, uint16_t nSQLPort ) : CBaseCallable( ), CCallableDotAGameAdd( nGameID, nWinner, nMin, nSec ), CMySQLCallable( nConnection, nSQLBotID, nSQLServer, nSQLDatabase, nSQLUser, nSQLPassword, nSQLPort ) { }
	virtual ~CMySQLCallableDotAGameAdd( ) { }

	virtual void operator( )( );
	virtual void Init( ) { CMySQLCallable :: Init( ); }
	virtual void Close( ) { CMySQLCallable :: Close( ); }
};

class CMySQLCallableDotAPlayerAdd : public CCallableDotAPlayerAdd, public CMySQLCallable
{
public:
	CMySQLCallableDotAPlayerAdd( uint32_t nGameID, uint32_t nColour, uint32_t nKills, uint32_t nDeaths, uint32_t nCreepKills, uint32_t nCreepDenies, uint32_t nAssists, uint32_t nGold, uint32_t nNeutralKills, string nItem1, string nItem2, string nItem3, string nItem4, string nItem5, string nItem6, string nHero, uint32_t nNewColour, uint32_t nTowerKills, uint32_t nRaxKills, uint32_t nCourierKills, uint32_t nLevel, void *nConnection, uint32_t nSQLBotID, string nSQLServer, string nSQLDatabase, string nSQLUser, string nSQLPassword, uint16_t nSQLPort ) : CBaseCallable( ), CCallableDotAPlayerAdd( nGameID, nColour, nKills, nDeaths, nCreepKills, nCreepDenies, nAssists, nGold, nNeutralKills, nItem1, nItem2, nItem3, nItem4, nItem5, nItem6, nHero, nNewColour, nTowerKills, nRaxKills, nCourierKills, nLevel ), CMySQLCallable( nConnection, nSQLBotID, nSQLServer, nSQLDatabase, nSQLUser, nSQLPassword, nSQLPort ) { }
	virtual ~CMySQLCallableDotAPlayerAdd( ) { }

	virtual void operator( )( );
	virtual void Init( ) { CMySQLCallable :: Init( ); }
	virtual void Close( ) { CMySQLCallable :: Close( ); }
};

class CMySQLCallableDotAPlayerSummaryCheck : public CCallableDotAPlayerSummaryCheck, public CMySQLCallable
{
public:
	CMySQLCallableDotAPlayerSummaryCheck( string nName, void *nConnection, uint32_t nSQLBotID, string nSQLServer, string nSQLDatabase, string nSQLUser, string nSQLPassword, uint16_t nSQLPort ) : CBaseCallable( ), CCallableDotAPlayerSummaryCheck( nName ), CMySQLCallable( nConnection, nSQLBotID, nSQLServer, nSQLDatabase, nSQLUser, nSQLPassword, nSQLPort ) { }
	virtual ~CMySQLCallableDotAPlayerSummaryCheck( ) { }

	virtual void operator( )( );
	virtual void Init( ) { CMySQLCallable :: Init( ); }
	virtual void Close( ) { CMySQLCallable :: Close( ); }
};

class CMySQLCallableDownloadAdd : public CCallableDownloadAdd, public CMySQLCallable
{
public:
	CMySQLCallableDownloadAdd( string nMap, uint32_t nMapSize, string nName, string nIP, uint32_t nSpoofed, string nSpoofedRealm, uint32_t nDownloadTime, void *nConnection, uint32_t nSQLBotID, string nSQLServer, string nSQLDatabase, string nSQLUser, string nSQLPassword, uint16_t nSQLPort ) : CBaseCallable( ), CCallableDownloadAdd( nMap, nMapSize, nName, nIP, nSpoofed, nSpoofedRealm, nDownloadTime ), CMySQLCallable( nConnection, nSQLBotID, nSQLServer, nSQLDatabase, nSQLUser, nSQLPassword, nSQLPort ) { }
	virtual ~CMySQLCallableDownloadAdd( ) { }

	virtual void operator( )( );
	virtual void Init( ) { CMySQLCallable :: Init( ); }
	virtual void Close( ) { CMySQLCallable :: Close( ); }
};

class CMySQLCallableScoreCheck : public CCallableScoreCheck, public CMySQLCallable
{
public:
	CMySQLCallableScoreCheck( string nCategory, string nName, string nServer, void *nConnection, uint32_t nSQLBotID, string nSQLServer, string nSQLDatabase, string nSQLUser, string nSQLPassword, uint16_t nSQLPort ) : CBaseCallable( ), CCallableScoreCheck( nCategory, nName, nServer ), CMySQLCallable( nConnection, nSQLBotID, nSQLServer, nSQLDatabase, nSQLUser, nSQLPassword, nSQLPort ) { }
	virtual ~CMySQLCallableScoreCheck( ) { }

	virtual void operator( )( );
	virtual void Init( ) { CMySQLCallable :: Init( ); }
	virtual void Close( ) { CMySQLCallable :: Close( ); }
};

class CMySQLCallableW3MMDPlayerAdd : public CCallableW3MMDPlayerAdd, public CMySQLCallable
{
public:
	CMySQLCallableW3MMDPlayerAdd( string nCategory, uint32_t nGameID, uint32_t nPID, string nName, string nFlag, uint32_t nLeaver, uint32_t nPracticing, void *nConnection, uint32_t nSQLBotID, string nSQLServer, string nSQLDatabase, string nSQLUser, string nSQLPassword, uint16_t nSQLPort ) : CBaseCallable( ), CCallableW3MMDPlayerAdd( nCategory, nGameID, nPID, nName, nFlag, nLeaver, nPracticing ), CMySQLCallable( nConnection, nSQLBotID, nSQLServer, nSQLDatabase, nSQLUser, nSQLPassword, nSQLPort ) { }
	virtual ~CMySQLCallableW3MMDPlayerAdd( ) { }

	virtual void operator( )( );
	virtual void Init( ) { CMySQLCallable :: Init( ); }
	virtual void Close( ) { CMySQLCallable :: Close( ); }
};

class CMySQLCallableW3MMDVarAdd : public CCallableW3MMDVarAdd, public CMySQLCallable
{
public:
	CMySQLCallableW3MMDVarAdd( uint32_t nGameID, map<VarP,int32_t> nVarInts, void *nConnection, uint32_t nSQLBotID, string nSQLServer, string nSQLDatabase, string nSQLUser, string nSQLPassword, uint16_t nSQLPort ) : CBaseCallable( ), CCallableW3MMDVarAdd( nGameID, nVarInts ), CMySQLCallable( nConnection, nSQLBotID, nSQLServer, nSQLDatabase, nSQLUser, nSQLPassword, nSQLPort ) { }
	CMySQLCallableW3MMDVarAdd( uint32_t nGameID, map<VarP,double> nVarReals, void *nConnection, uint32_t nSQLBotID, string nSQLServer, string nSQLDatabase, string nSQLUser, string nSQLPassword, uint16_t nSQLPort ) : CBaseCallable( ), CCallableW3MMDVarAdd( nGameID, nVarReals ), CMySQLCallable( nConnection, nSQLBotID, nSQLServer, nSQLDatabase, nSQLUser, nSQLPassword, nSQLPort ) { }
	CMySQLCallableW3MMDVarAdd( uint32_t nGameID, map<VarP,string> nVarStrings, void *nConnection, uint32_t nSQLBotID, string nSQLServer, string nSQLDatabase, string nSQLUser, string nSQLPassword, uint16_t nSQLPort ) : CBaseCallable( ), CCallableW3MMDVarAdd( nGameID, nVarStrings ), CMySQLCallable( nConnection, nSQLBotID, nSQLServer, nSQLDatabase, nSQLUser, nSQLPassword, nSQLPort ) { }
	virtual ~CMySQLCallableW3MMDVarAdd( ) { }

	virtual void operator( )( );
	virtual void Init( ) { CMySQLCallable :: Init( ); }
	virtual void Close( ) { CMySQLCallable :: Close( ); }
};

#endif

#endif
