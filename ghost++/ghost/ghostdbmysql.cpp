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

#include "ghost.h"
#include "util.h"
#include "config.h"
#include "ghostdb.h"
#include "ghostdbmysql.h"
#include "ghost.h"
#include <signal.h>

#ifdef WIN32
 #include <winsock.h>
#endif

#include <mysql/mysql.h>
#include <boost/thread.hpp>

//
// CGHostDBMySQL
//

CGHostDBMySQL :: CGHostDBMySQL( CConfig *CFG ) : CGHostDB( CFG )
{
	m_Server = CFG->GetString( "db_mysql_server", string( ) );
	m_Database = CFG->GetString( "db_mysql_database", "ghost" );
	m_User = CFG->GetString( "db_mysql_user", string( ) );
	m_Password = CFG->GetString( "db_mysql_password", string( ) );
	m_Port = CFG->GetInt( "db_mysql_port", 0 );
	m_BotID = CFG->GetInt( "db_mysql_botid", 0 );
	m_NumConnections = 1;
	m_Name.clear();
	m_OutstandingCallables = 0;

	mysql_library_init( 0, NULL, NULL );

	// create the first connection

	CONSOLE_Print( "[MYSQL] connecting to database server" );
	MYSQL *Connection = NULL;

	if( !( Connection = mysql_init( NULL ) ) )
	{
		CONSOLE_Print( string( "[MYSQL] " ) + mysql_error( Connection ) );
		m_HasError = true;
		m_Error = "error initializing MySQL connection";
		return;
	}

	my_bool Reconnect = true;
	mysql_options( Connection, MYSQL_OPT_RECONNECT, &Reconnect );

	if( !( mysql_real_connect( Connection, m_Server.c_str( ), m_User.c_str( ), m_Password.c_str( ), m_Database.c_str( ), m_Port, NULL, 0 ) ) )
	{
		CONSOLE_Print( string( "[MYSQL] " ) + mysql_error( Connection ) );
		m_HasError = true;
		m_Error = "error connecting to MySQL server";
		return;
	}

	m_IdleConnections.push( Connection );
}

CGHostDBMySQL :: ~CGHostDBMySQL( )
{
	CONSOLE_Print( "[MYSQL] closing " + UTIL_ToString( m_IdleConnections.size( ) ) + "/" + UTIL_ToString( m_NumConnections ) + " idle MySQL connections" );

	while( !m_IdleConnections.empty( ) )
	{
		mysql_close( (MYSQL *)m_IdleConnections.front( ) );
		m_IdleConnections.pop( );
	}

	if( m_OutstandingCallables > 0 )
		CONSOLE_Print( "[MYSQL] " + UTIL_ToString( m_OutstandingCallables ) + " outstanding callables were never recovered" );

	mysql_library_end( );
}

string CGHostDBMySQL :: GetStatus( )
{
	//DEBUG OPTION
	//return "DB STATUS --- Connections: " + UTIL_ToString( m_IdleConnections.size( ) ) + "/" + UTIL_ToString( m_NumConnections ) + " idle. Outstanding callables: " + UTIL_ToString( m_OutstandingCallables ) + ".";
        for( vector<string> :: iterator i = m_Name.begin( ); i != m_Name.end( ); ++i )
        {
		CONSOLE_Print( *i );
	}
	m_Name.clear( );
	return "DB STATUS --- Connections: " + UTIL_ToString( m_IdleConnections.size( ) ) + "/" + UTIL_ToString( m_NumConnections ) + " idle. Outstanding callables: " + UTIL_ToString( m_OutstandingCallables ) + ".";
}
/*
void CGHostDBMySQL :: RecoverCallable( CBaseCallable *callable )
{
	CMySQLCallable *MySQLCallable = dynamic_cast<CMySQLCallable *>( callable );

	if( MySQLCallable )
	{
		if( m_IdleConnections.size( ) > 30 )
		{
			mysql_close( (MYSQL *)MySQLCallable->GetConnection( ) );
                        --m_NumConnections;
		}
		else
			m_IdleConnections.push( MySQLCallable->GetConnection( ) );

		if( m_OutstandingCallables == 0 )
			CONSOLE_Print( "[MYSQL] recovered a mysql callable with zero outstanding" );
		else
                        --m_OutstandingCallables;

		if( !MySQLCallable->GetError( ).empty( ) )
			CONSOLE_Print( "[MYSQL] error --- " + MySQLCallable->GetError( ) );
	}
	else
		CONSOLE_Print( "[MYSQL] tried to recover a non-mysql callable" );
}
*/
void CGHostDBMySQL :: RecoverCallable( CBaseCallable *callable )
{
	CMySQLCallable *MySQLCallable = dynamic_cast<CMySQLCallable *>( callable );

	if( MySQLCallable )
	{
		if( !MySQLCallable->GetError( ).empty( ) )
			CONSOLE_Print( "[MYSQL] error --- " + MySQLCallable->GetError( ) );

		if( m_IdleConnections.size( ) > 30 || !MySQLCallable->GetError( ).empty( ) )
		{
			mysql_close( (MYSQL *)MySQLCallable->GetConnection( ) );
                        --m_NumConnections;
		}
		else
			m_IdleConnections.push( MySQLCallable->GetConnection( ) );

		if( m_OutstandingCallables == 0 )
			CONSOLE_Print( "[MYSQL] recovered a mysql callable with zero outstanding" );
		else
                        --m_OutstandingCallables;
	}
	else
		CONSOLE_Print( "[MYSQL] tried to recover a non-mysql callable" );
}

std::vector<std::string> &split2(const std::string &s, char delim, std::vector<std::string> &elems) {
    std::stringstream ss(s);
    std::string item;
    while (std::getline(ss, item, delim)) {
        elems.push_back(item);
    }
    return elems;
}

std::vector<std::string> split2(const std::string &s, char delim) {
    std::vector<std::string> elems;
    split2(s, delim, elems);
    return elems;
}

bool HasSpecialCharacters(const char *str)
{
	return str[strspn(str, "0123456789.")] != 0;
}

void CGHostDBMySQL :: CreateThread( CBaseCallable *callable )
{
	try
	{
		boost :: thread Thread( boost :: ref( *callable ) );
	}
	catch( boost :: thread_resource_error tre )
	{
		CONSOLE_Print( "[MYSQL] error spawning thread on attempt #1 [" + string( tre.what( ) ) + "], pausing execution and trying again in 50ms" );
		MILLISLEEP( 50 );

		try
		{
			boost :: thread Thread( boost :: ref( *callable ) );
		}
		catch( boost :: thread_resource_error tre2 )
		{
			CONSOLE_Print( "[MYSQL] error spawning thread on attempt #2 [" + string( tre2.what( ) ) + "], giving up" );
			callable->SetReady( true );
		}
	}
}

CCallableFromCheck *CGHostDBMySQL :: ThreadedFromCheck( string ip )
{
        void *Connection = GetIdleConnection( );

        if( !Connection )
                ++m_NumConnections;

        CCallableFromCheck *Callable = new CMySQLCallableFromCheck( ip, Connection, m_BotID, m_Server, m_Database, m_User, m_Password, m_Port );
        CreateThread( Callable );
        ++m_OutstandingCallables;
        m_Name.push_back( "fromcheck" );
        return Callable;
}

CCallableRegAdd *CGHostDBMySQL :: ThreadedRegAdd( string user, string server, string mail, string password, string type )
{
        void *Connection = GetIdleConnection( );

        if( !Connection )
                ++m_NumConnections;

	CCallableRegAdd *Callable = new CMySQLCallableRegAdd( user, server, mail, password, type, Connection, m_BotID, m_Server, m_Database, m_User, m_Password, m_Port );
        CreateThread( Callable );
        ++m_OutstandingCallables;
        m_Name.push_back( "reg" );
        return Callable;
}

CCallableStatsSystem *CGHostDBMySQL :: ThreadedStatsSystem( string user, string input, uint32_t one, string type )
{
        void *Connection = GetIdleConnection( );

        if( !Connection )
                ++m_NumConnections;

	CCallableStatsSystem *Callable = new CMySQLCallableStatsSystem( user, input, one, type, Connection, m_BotID, m_Server, m_Database, m_User, m_Password, m_Port );
        CreateThread( Callable );
        ++m_OutstandingCallables;
        m_Name.push_back( "stats" );
        return Callable;
}

CCallablePWCheck *CGHostDBMySQL :: ThreadedPWCheck( string user )
{
        void *Connection = GetIdleConnection( );

        if( !Connection )
                ++m_NumConnections;

        CCallablePWCheck *Callable = new CMySQLCallablePWCheck( user, Connection, m_BotID, m_Server, m_Database, m_User, m_Password, m_Port );
        CreateThread( Callable );
        ++m_OutstandingCallables;
        m_Name.push_back( "pw" );
        return Callable;
}

CCallablePassCheck *CGHostDBMySQL :: ThreadedPassCheck( string user, string pass, uint32_t st )
{
        void *Connection = GetIdleConnection( );

        if( !Connection )
                ++m_NumConnections;

        CCallablePassCheck *Callable = new CMySQLCallablePassCheck( user, pass, st, Connection, m_BotID, m_Server, m_Database, m_User, m_Password, m_Port );
        CreateThread( Callable );
        ++m_OutstandingCallables;
        m_Name.push_back( "pass" );
        return Callable;
}

CCallablepm *CGHostDBMySQL :: Threadedpm( string user, string listener, uint32_t status, string message, string type )
{
        void *Connection = GetIdleConnection( );

        if( !Connection )
                ++m_NumConnections;

        CCallablepm *Callable = new CMySQLCallablepm( user, listener, status, message, type, Connection, m_BotID, m_Server, m_Database, m_User, m_Password, m_Port );
        CreateThread( Callable );
        ++m_OutstandingCallables;
        m_Name.push_back( "pm" );
        return Callable;
}

CCallablePList *CGHostDBMySQL :: ThreadedPList( string server )
{
        void *Connection = GetIdleConnection( );

        if( !Connection )
                ++m_NumConnections;

        CCallablePList *Callable = new CMySQLCallablePList( server, Connection, m_BotID, m_Server, m_Database, m_User, m_Password, m_Port );
        CreateThread( Callable );
        ++m_OutstandingCallables;
        m_Name.push_back( "plist" );
        return Callable;
}

CCallableFlameList *CGHostDBMySQL :: ThreadedFlameList( )
{
        void *Connection = GetIdleConnection( );

        if( !Connection )
                ++m_NumConnections;

        CCallableFlameList *Callable = new CMySQLCallableFlameList( Connection, m_BotID, m_Server, m_Database, m_User, m_Password, m_Port );
        CreateThread( Callable );
        ++m_OutstandingCallables;
        m_Name.push_back( "flame" );
        return Callable;
}

CCallableAnnounceList *CGHostDBMySQL :: ThreadedAnnounceList( )
{
        void *Connection = GetIdleConnection( );

        if( !Connection )
                ++m_NumConnections;

        CCallableAnnounceList *Callable = new CMySQLCallableAnnounceList( Connection, m_BotID, m_Server, m_Database, m_User, m_Password, m_Port );
        CreateThread( Callable );
        ++m_OutstandingCallables;
        m_Name.push_back( "AnnounceList" );
        return Callable;
}

CCallableDCountryList *CGHostDBMySQL :: ThreadedDCountryList( )
{
        void *Connection = GetIdleConnection( );

        if( !Connection )
                ++m_NumConnections;

        CCallableDCountryList *Callable = new CMySQLCallableDCountryList( Connection, m_BotID, m_Server, m_Database, m_User, m_Password, m_Port );
        CreateThread( Callable );
        ++m_OutstandingCallables;
        m_Name.push_back( "countrylist" );
        return Callable;
}

CCallableStoreLog *CGHostDBMySQL :: ThreadedStoreLog( uint32_t chatid, string game, vector<string> admin )
{
        void *Connection = GetIdleConnection( );

        if( !Connection )
                ++m_NumConnections;

        CCallableStoreLog *Callable =new CMySQLCallableStoreLog( chatid, game, admin, Connection, m_BotID, m_Server, m_Database, m_User, m_Password, m_Port );
        CreateThread( Callable );
        ++m_OutstandingCallables;
        m_Name.push_back( "store" );
        return Callable;
}

CCallablegs *CGHostDBMySQL :: Threadedgs( uint32_t chatid, string gn, uint32_t st, uint32_t gametype )
{
        void *Connection = GetIdleConnection( );

        if( !Connection )
                ++m_NumConnections;

        CCallablegs *Callable =new CMySQLCallablegs( chatid, gn, st, gametype, Connection, m_BotID, m_Server, m_Database, m_User, m_Password, m_Port );
        CreateThread( Callable );
        ++m_OutstandingCallables;
        m_Name.push_back( "gs" );
        return Callable;
}

CCallablepenp *CGHostDBMySQL :: Threadedpenp( string name, string reason, string admin, uint32_t amount, string type )
{
        void *Connection = GetIdleConnection( );

        if( !Connection )
                ++m_NumConnections;

        CCallablepenp *Callable =new CMySQLCallablepenp( name, reason, admin, amount, type, Connection, m_BotID, m_Server, m_Database, m_User, m_Password, m_Port );
        CreateThread( Callable );
        ++m_OutstandingCallables;
        return Callable;
}

CCallableBanCount *CGHostDBMySQL :: ThreadedBanCount( string server )
{
	void *Connection = GetIdleConnection( );

	if( !Connection )
                ++m_NumConnections;

	CCallableBanCount *Callable = new CMySQLCallableBanCount( server, Connection, m_BotID, m_Server, m_Database, m_User, m_Password, m_Port );
	CreateThread( Callable );
        ++m_OutstandingCallables;
	return Callable;
}

CCallableBanCheck *CGHostDBMySQL :: ThreadedBanCheck( string server, string user, string ip )
{
	void *Connection = GetIdleConnection( );

	if( !Connection )
                ++m_NumConnections;

	CCallableBanCheck *Callable = new CMySQLCallableBanCheck( server, user, ip, Connection, m_BotID, m_Server, m_Database, m_User, m_Password, m_Port );
	CreateThread( Callable );
        ++m_OutstandingCallables;
	return Callable;
}

CCallableBanCheck2 *CGHostDBMySQL :: ThreadedBanCheck2( string server, string user, string type )
{
        void *Connection = GetIdleConnection( );

        if( !Connection )
                ++m_NumConnections;

        CCallableBanCheck2 *Callable = new CMySQLCallableBanCheck2( server, user, type, Connection, m_BotID, m_Server, m_Database, m_User, m_Password, m_Port );
        CreateThread( Callable );
        ++m_OutstandingCallables;
        return Callable;
}

CCallableBanAdd *CGHostDBMySQL :: ThreadedBanAdd( string server, string user, string ip, string gamename, string admin, string reason, uint32_t bantime, string country )
{
	void *Connection = GetIdleConnection( );

	if( !Connection )
                ++m_NumConnections;

	CCallableBanAdd *Callable = new CMySQLCallableBanAdd( server, user, ip, gamename, admin, reason, bantime, country, Connection, m_BotID, m_Server, m_Database, m_User, m_Password, m_Port );
	CreateThread( Callable );
        ++m_OutstandingCallables;
	return Callable;
}

CCallablePUp *CGHostDBMySQL :: ThreadedPUp( string name, uint32_t level, string realm, string user )
{
        void *Connection = GetIdleConnection( );

        if( !Connection )
                ++m_NumConnections;

        CCallablePUp *Callable = new CMySQLCallablePUp( name, level, realm, user, Connection, m_BotID, m_Server, m_Database, m_User, m_Password, m_Port );
        CreateThread( Callable );
        ++m_OutstandingCallables;
        return Callable;
}

CCallableBanRemove *CGHostDBMySQL :: ThreadedBanRemove( string server, string user )
{
	void *Connection = GetIdleConnection( );

	if( !Connection )
                ++m_NumConnections;

	CCallableBanRemove *Callable = new CMySQLCallableBanRemove( server, user, Connection, m_BotID, m_Server, m_Database, m_User, m_Password, m_Port );
	CreateThread( Callable );
        ++m_OutstandingCallables;
	return Callable;
}

CCallableBanRemove *CGHostDBMySQL :: ThreadedBanRemove( string user )
{
	void *Connection = GetIdleConnection( );

	if( !Connection )
                ++m_NumConnections;

	CCallableBanRemove *Callable = new CMySQLCallableBanRemove( string( ), user, Connection, m_BotID, m_Server, m_Database, m_User, m_Password, m_Port );
	CreateThread( Callable );
        ++m_OutstandingCallables;
	return Callable;
}

CCallableBanList *CGHostDBMySQL :: ThreadedBanList( string server )
{
	void *Connection = GetIdleConnection( );

	if( !Connection )
                ++m_NumConnections;

	CCallableBanList *Callable = new CMySQLCallableBanList( server, Connection, m_BotID, m_Server, m_Database, m_User, m_Password, m_Port );
	CreateThread( Callable );
        ++m_OutstandingCallables;
	return Callable;
}

CCallableTBRemove *CGHostDBMySQL :: ThreadedTBRemove( string server )
{
        void *Connection = GetIdleConnection( );

        if( !Connection )
                ++m_NumConnections;

        CCallableTBRemove *Callable = new CMySQLCallableTBRemove( server, Connection, m_BotID, m_Server, m_Database, m_User, m_Password, m_Port );
        CreateThread( Callable );
        ++m_OutstandingCallables;
        m_Name.push_back( "tbr" );
        return Callable;
}

CCallableCommandList *CGHostDBMySQL :: ThreadedCommandList( )
{
	void *Connection = GetIdleConnection( );

	if( !Connection )
                ++m_NumConnections;

	CCallableCommandList *Callable = new CMySQLCallableCommandList( Connection, m_BotID, m_Server, m_Database, m_User, m_Password, m_Port );
	CreateThread( Callable );
        ++m_OutstandingCallables;
        m_Name.push_back( "commandlist" );
	return Callable;
}

CCallableClean *CGHostDBMySQL :: ThreadedClean( )
{
        void *Connection = GetIdleConnection( );

        if( !Connection )
                ++m_NumConnections;

        CCallableClean *Callable = new CMySQLCallableClean( Connection, m_BotID, m_Server, m_Database, m_User, m_Password, m_Port );
        CreateThread( Callable );
        ++m_OutstandingCallables;
        m_Name.push_back( "clean" );
        return Callable;
}

CCallableGameAdd *CGHostDBMySQL :: ThreadedGameAdd( string server, string map, string gamename, string ownername, uint32_t duration, uint32_t gamestate, string creatorname, string creatorserver, uint32_t gametype, vector<string> lobbylog, vector<string> gamelog )
{
	void *Connection = GetIdleConnection( );

	if( !Connection )
                ++m_NumConnections;

	CCallableGameAdd *Callable = new CMySQLCallableGameAdd( server, map, gamename, ownername, duration, gamestate, creatorname, creatorserver, gametype, lobbylog, gamelog, Connection, m_BotID, m_Server, m_Database, m_User, m_Password, m_Port );
	CreateThread( Callable );
        ++m_OutstandingCallables;
	return Callable;
}

CCallableGamePlayerAdd *CGHostDBMySQL :: ThreadedGamePlayerAdd( uint32_t gameid, string name, string ip, uint32_t spoofed, string spoofedrealm, uint32_t reserved, uint32_t loadingtime, uint32_t left, string leftreason, uint32_t team, uint32_t colour )
{
	void *Connection = GetIdleConnection( );

	if( !Connection )
                ++m_NumConnections;

	CCallableGamePlayerAdd *Callable = new CMySQLCallableGamePlayerAdd( gameid, name, ip, spoofed, spoofedrealm, reserved, loadingtime, left, leftreason, team, colour, Connection, m_BotID, m_Server, m_Database, m_User, m_Password, m_Port );
	CreateThread( Callable );
        ++m_OutstandingCallables;
	return Callable;
}

CCallableGameUpdate *CGHostDBMySQL :: ThreadedGameUpdate( string map, string gamename, string ownername, string creatorname, uint32_t players, string usernames, uint32_t slotsTotal, uint32_t totalGames, uint32_t totalPlayers, bool add )
{
	void *Connection = GetIdleConnection( );

	if( !Connection )
                ++m_NumConnections;

	CCallableGameUpdate *Callable = new CMySQLCallableGameUpdate( map, gamename, ownername, creatorname, players, usernames, slotsTotal, totalGames, totalPlayers, add, Connection, m_BotID, m_Server, m_Database, m_User, m_Password, m_Port );
	CreateThread( Callable );
        ++m_OutstandingCallables;
	return Callable;
}

CCallableGamePlayerSummaryCheck *CGHostDBMySQL :: ThreadedGamePlayerSummaryCheck( string name )
{
	void *Connection = GetIdleConnection( );

	if( !Connection )
                ++m_NumConnections;

	CCallableGamePlayerSummaryCheck *Callable = new CMySQLCallableGamePlayerSummaryCheck( name, Connection, m_BotID, m_Server, m_Database, m_User, m_Password, m_Port );
	CreateThread( Callable );
        ++m_OutstandingCallables;
	return Callable;
}

CCallableStatsPlayerSummaryCheck *CGHostDBMySQL :: ThreadedStatsPlayerSummaryCheck( string name )
{
        void *Connection = GetIdleConnection( );

        if( !Connection )
                ++m_NumConnections;

        CCallableStatsPlayerSummaryCheck *Callable = new CMySQLCallableStatsPlayerSummaryCheck( name, Connection, m_BotID, m_Server, m_Database, m_User, m_Password, m_Port );
        CreateThread( Callable );
        ++m_OutstandingCallables;
        return Callable;
}


CCallableInboxSummaryCheck *CGHostDBMySQL :: ThreadedInboxSummaryCheck( string name )
{
        void *Connection = GetIdleConnection( );

        if( !Connection )
                ++m_NumConnections;

        CCallableInboxSummaryCheck *Callable = new CMySQLCallableInboxSummaryCheck( name, Connection, m_BotID, m_Server, m_Database, m_User, m_Password, m_Port );
        CreateThread( Callable );
        ++m_OutstandingCallables;
        m_Name.push_back( "inbox" );
        return Callable;
}

CCallableDotAGameAdd *CGHostDBMySQL :: ThreadedDotAGameAdd( uint32_t gameid, uint32_t winner, uint32_t min, uint32_t sec )
{
	void *Connection = GetIdleConnection( );

	if( !Connection )
                ++m_NumConnections;

	CCallableDotAGameAdd *Callable = new CMySQLCallableDotAGameAdd( gameid, winner, min, sec, Connection, m_BotID, m_Server, m_Database, m_User, m_Password, m_Port );
	CreateThread( Callable );
        ++m_OutstandingCallables;
	return Callable;
}

CCallableDotAPlayerAdd *CGHostDBMySQL :: ThreadedDotAPlayerAdd( uint32_t gameid, uint32_t colour, uint32_t kills, uint32_t deaths, uint32_t creepkills, uint32_t creepdenies, uint32_t assists, uint32_t gold, uint32_t neutralkills, string item1, string item2, string item3, string item4, string item5, string item6, string hero, uint32_t newcolour, uint32_t towerkills, uint32_t raxkills, uint32_t courierkills, uint32_t level )
{
	void *Connection = GetIdleConnection( );

	if( !Connection )
                ++m_NumConnections;

	CCallableDotAPlayerAdd *Callable = new CMySQLCallableDotAPlayerAdd( gameid, colour, kills, deaths, creepkills, creepdenies, assists, gold, neutralkills, item1, item2, item3, item4, item5, item6, hero, newcolour, towerkills, raxkills, courierkills, level, Connection, m_BotID, m_Server, m_Database, m_User, m_Password, m_Port );
	CreateThread( Callable );
        ++m_OutstandingCallables;
	return Callable;
}

CCallableDotAPlayerSummaryCheck *CGHostDBMySQL :: ThreadedDotAPlayerSummaryCheck( string name )
{
	void *Connection = GetIdleConnection( );

	if( !Connection )
                ++m_NumConnections;

	CCallableDotAPlayerSummaryCheck *Callable = new CMySQLCallableDotAPlayerSummaryCheck( name, Connection, m_BotID, m_Server, m_Database, m_User, m_Password, m_Port );
	CreateThread( Callable );
        ++m_OutstandingCallables;
	return Callable;
}

CCallableDownloadAdd *CGHostDBMySQL :: ThreadedDownloadAdd( string map, uint32_t mapsize, string name, string ip, uint32_t spoofed, string spoofedrealm, uint32_t downloadtime )
{
	void *Connection = GetIdleConnection( );

	if( !Connection )
                ++m_NumConnections;

	CCallableDownloadAdd *Callable = new CMySQLCallableDownloadAdd( map, mapsize, name, ip, spoofed, spoofedrealm, downloadtime, Connection, m_BotID, m_Server, m_Database, m_User, m_Password, m_Port );
	CreateThread( Callable );
        ++m_OutstandingCallables;
	return Callable;
}

CCallableScoreCheck *CGHostDBMySQL :: ThreadedScoreCheck( string category, string name, string server )
{
	void *Connection = GetIdleConnection( );

	if( !Connection )
                ++m_NumConnections;

	CCallableScoreCheck *Callable = new CMySQLCallableScoreCheck( category, name, server, Connection, m_BotID, m_Server, m_Database, m_User, m_Password, m_Port );
	CreateThread( Callable );
        ++m_OutstandingCallables;
	return Callable;
}

CCallableW3MMDPlayerAdd *CGHostDBMySQL :: ThreadedW3MMDPlayerAdd( string category, uint32_t gameid, uint32_t pid, string name, string flag, uint32_t leaver, uint32_t practicing )
{
	void *Connection = GetIdleConnection( );

	if( !Connection )
                ++m_NumConnections;

	CCallableW3MMDPlayerAdd *Callable = new CMySQLCallableW3MMDPlayerAdd( category, gameid, pid, name, flag, leaver, practicing, Connection, m_BotID, m_Server, m_Database, m_User, m_Password, m_Port );
	CreateThread( Callable );
        ++m_OutstandingCallables;
	return Callable;
}

CCallableW3MMDVarAdd *CGHostDBMySQL :: ThreadedW3MMDVarAdd( uint32_t gameid, map<VarP,int32_t> var_ints )
{
	void *Connection = GetIdleConnection( );

	if( !Connection )
                ++m_NumConnections;

	CCallableW3MMDVarAdd *Callable = new CMySQLCallableW3MMDVarAdd( gameid, var_ints, Connection, m_BotID, m_Server, m_Database, m_User, m_Password, m_Port );
	CreateThread( Callable );
        ++m_OutstandingCallables;
	return Callable;
}

CCallableW3MMDVarAdd *CGHostDBMySQL :: ThreadedW3MMDVarAdd( uint32_t gameid, map<VarP,double> var_reals )
{
	void *Connection = GetIdleConnection( );

	if( !Connection )
                ++m_NumConnections;

	CCallableW3MMDVarAdd *Callable = new CMySQLCallableW3MMDVarAdd( gameid, var_reals, Connection, m_BotID, m_Server, m_Database, m_User, m_Password, m_Port );
	CreateThread( Callable );
        ++m_OutstandingCallables;
	return Callable;
}

CCallableW3MMDVarAdd *CGHostDBMySQL :: ThreadedW3MMDVarAdd( uint32_t gameid, map<VarP,string> var_strings )
{
	void *Connection = GetIdleConnection( );

	if( !Connection )
                ++m_NumConnections;

	CCallableW3MMDVarAdd *Callable = new CMySQLCallableW3MMDVarAdd( gameid, var_strings, Connection, m_BotID, m_Server, m_Database, m_User, m_Password, m_Port );
	CreateThread( Callable );
        ++m_OutstandingCallables;
	return Callable;
}

void *CGHostDBMySQL :: GetIdleConnection( )
{
	void *Connection = NULL;

	if( !m_IdleConnections.empty( ) )
	{
		Connection = m_IdleConnections.front( );
		m_IdleConnections.pop( );
	}

	return Connection;
}

//
// unprototyped global helper functions
//

string MySQLEscapeString( void *conn, string str )
{
	char *to = new char[str.size( ) * 2 + 1];
	unsigned long size = mysql_real_escape_string( (MYSQL *)conn, to, str.c_str( ), str.size( ) );
	string result( to, size );
	delete [] to;
	return result;
}

vector<string> MySQLFetchRow( MYSQL_RES *res )
{
	vector<string> Result;

	MYSQL_ROW Row = mysql_fetch_row( res );

	if( Row )
	{
		unsigned long *Lengths;
		Lengths = mysql_fetch_lengths( res );

                for( unsigned int i = 0; i < mysql_num_fields( res ); ++i )
		{
			if( Row[i] )
				Result.push_back( string( Row[i], Lengths[i] ) );
			else
				Result.push_back( string( ) );
		}
	}

	return Result;
}

//
// global helper functions
//
bool MySQLClean( void *conn, string *error, uint32_t botid )
{
        // clean up
        string CleanQuery= "TRUNCATE TABLE oh_game_status WHERE botid = '" + UTIL_ToString( botid ) +"'; TRUNCATE TABLE oh_game_log WHERE botid = '" + UTIL_ToString( botid ) +"';";
        if( mysql_real_query( (MYSQL *)conn, CleanQuery.c_str( ), CleanQuery.size( ) ) != 0 )
                *error = mysql_error( (MYSQL *)conn );
        else
                CONSOLE_Print( "Truncated oh_games_stats & oh_game_log table" );

}

string MySQLFromCheck( void *conn, string *error, uint32_t botid, string ip )
{
	string EscIP = MySQLEscapeString( conn, ip );
	string CountryLetter = "??";
	string Country = "unknown";
//	CONSOLE_Print( "Checking IP: "+EscIP);

	std::string tip = EscIP;
	const char * c = tip.c_str();
	if( HasSpecialCharacters( c ) )
		CONSOLE_Print( "Found special caracters on: "+EscIP );

	string Query = "SELECT code, country FROM  `oh_geoip` WHERE INET_ATON('"+EscIP+"') BETWEEN ip_start_int AND ip_end_int;";
        if( mysql_real_query( (MYSQL *)conn, Query.c_str( ), Query.size( ) ) != 0 )
        {
                *error = mysql_error( (MYSQL *)conn );
                return "?? unknown";
        }
        else
        {
                MYSQL_RES *Result = mysql_store_result( (MYSQL *)conn );
                if (Result)
                {
                        vector<string> Row = MySQLFetchRow( Result );
                        if (Row.size( ) == 2)
                        {
                                CountryLetter = Row[0];
                                Country = Row[1];
				return CountryLetter+" "+Country;
                        }
                        mysql_free_result( Result );
                }
                else
                        *error = mysql_error( (MYSQL *)conn );
        }

	return CountryLetter+" "+Country;
}

uint32_t MySQLRegAdd( void *conn, string *error, uint32_t botid, string user, string server, string mail, string password, string type )
{
        uint32_t RowID = 0;
        transform( user.begin( ), user.end( ), user.begin( ), (int(*)(int))tolower );
        transform( mail.begin( ), mail.end( ), mail.begin( ), (int(*)(int))tolower );
        string EscServer = MySQLEscapeString( conn, server );
        string EscName = MySQLEscapeString( conn, user );
        string EscMail = MySQLEscapeString( conn, mail );
        string EscPassword = MySQLEscapeString( conn, password );
        string QueryCheck = "SELECT `bnet_username`, `user_ppwd`, `user_email` from oh_users where user_name = '" + EscName + "' or bnet_username = '" + EscName + "' or user_email = '" + EscMail + "'";
	bool isUser = false;
	string Pass = "";
	string Mail = "";

        if( mysql_real_query( (MYSQL *)conn, QueryCheck.c_str( ), QueryCheck.size( ) ) != 0 )
       	{
               	*error = mysql_error( (MYSQL *)conn );
               	return 0;
       	}
       	else
       	{
               	//RowID = mysql_insert_id( (MYSQL *)conn );
               	MYSQL_RES *Result = mysql_store_result( (MYSQL *)conn );
               	if (Result)
               	{
                       	vector<string> Row = MySQLFetchRow( Result );
                        if (Row.size( ) == 3)
			{
                                isUser = true;
				Pass = Row[1];
				Mail = Row[2];
			}
			mysql_free_result( Result );
               	}
		else
			*error = mysql_error( (MYSQL *)conn );
        }

	if( type == "r" && !isUser )
	{
	        string Query = "INSERT INTO oh_users ( user_name, bnet_username, user_email, user_realm, admin_realm, user_password, user_bnet, user_joined ) VALUES ( '" + EscName + "', '" + EscName + "', '" + EscMail + "', '" + EscServer + "', '" + EscServer + "', '" + EscPassword + "', '1', UNIX_TIMESTAMP() )";

	        if( mysql_real_query( (MYSQL *)conn, Query.c_str( ), Query.size( ) ) != 0 )
        	        *error = mysql_error( (MYSQL *)conn );

	        return 1;
	}
	else if( type == "r" && isUser )
		return 5;
	else if( type == "c" && isUser )
	{
		if( Pass != EscPassword )
			return 3;
		else if( Mail != EscMail )
			return 4;
		else
		{
			string Query = "UPDATE `oh_users` SET `user_bnet` = '2', `admin_realm` = '" + EscServer + "', `bnet_username` = '" + EscName + "' WHERE `user_email` = '" + EscMail + "';";
        	        if( mysql_real_query( (MYSQL *)conn, Query.c_str( ), Query.size( ) ) != 0 )
	                        *error = mysql_error( (MYSQL *)conn );
			else
				return 2;
		}
	}
	else if( type == "c" && !isUser )
		return 6;

	return 0;
}

string MySQLStatsSystem( void *conn, string *error, uint32_t botid, string user, string input, uint32_t one, string type )
{
	transform( user.begin( ), user.end( ), user.begin( ), (int(*)(int))tolower );
	string EscUser = MySQLEscapeString( conn, user );
	string EscInput = MySQLEscapeString( conn, input );

	if( type == "betcheck" || type == "bet" )
	{
		string CheckQuery = "SELECT `points`, `points_bet` FROM `oh_stats` WHERE `player_lower` = '" + EscUser + "';";
		uint32_t currentpoints = 0;
		uint32_t betpoints = 0;
        	if( mysql_real_query( (MYSQL *)conn, CheckQuery.c_str( ), CheckQuery.size( ) ) != 0 )
                	*error = mysql_error( (MYSQL *)conn );
	        else
        	{
                	MYSQL_RES *Result = mysql_store_result( (MYSQL *)conn );

	                if( Result )
        	        {
                	        vector<string> Row = MySQLFetchRow( Result );

	                        if( Row.size( ) == 2 )
				{
					currentpoints = UTIL_ToUInt32( Row[0] );
					betpoints = UTIL_ToUInt32( Row[1] );
				}
	                        else
        	                        return "not listed";

	                        mysql_free_result( Result );
        	        }
		}

		if( type == "betcheck" )
			return UTIL_ToString( currentpoints );
		else if( type == "bet" && betpoints != 0 )
			return "already bet";
		else if( type == "bet" && one > currentpoints )
			return UTIL_ToString( currentpoints  );
		else if( type == "bet" )
		{
			string BetQuery = "UPDATE `oh_stats` SET `points_bet` = '" + UTIL_ToString( one ) + "' WHERE `player_lower` = '" + EscUser + "';";
	        	if( mysql_real_query( (MYSQL *)conn, BetQuery.c_str( ), BetQuery.size( ) ) != 0 )
	                	*error = mysql_error( (MYSQL *)conn );
	        	else
			{
				return "successfully bet";
                        	*error = mysql_error( (MYSQL *)conn );
			}
		}
		return "failed";
	}
	if( type == "statsreset" )
	{
		string ResetQuery = "UPDATE `oh_stats` SET score = 0, games = 0, wins = 0, losses = 0, draw = 0, kills = 0, deaths = 0, assists = 0, creeps = 0, denies = 0, neutrals = 0, towers = 0, rax = 0, streak = 0, maxstreak = 0, losingstreak = 0, maxlosingstreak = 0, points = 0, points_bet = 0, leaver = 0 WHERE player_lower = '"+EscUser+"';";
                if( mysql_real_query( (MYSQL *)conn, ResetQuery.c_str( ), ResetQuery.size( ) ) != 0 )
                        *error = mysql_error( (MYSQL *)conn );
                else
			return "success";

		return "failed";
	}
	if( type == "aliascheck" )
	{
        	string GetIP = "SELECT `ip` FROM `oh_gameplayers` WHERE name = '" + EscUser + "' AND `ip` != '0' AND `ip` != '0.0.0.0' ORDER BY `id` DESC;";
        	string UserIP = "";
	        if( mysql_real_query( (MYSQL *)conn, GetIP.c_str( ), GetIP.size( ) ) != 0 )
                	*error = mysql_error( (MYSQL *)conn );
        	else
	        {
                	MYSQL_RES *Result = mysql_store_result( (MYSQL *)conn );
        	        if( Result )
	                {
                        	vector<string> Row = MySQLFetchRow( Result );
                        	if( Row.size( ) == 1 )
                        	        UserIP = Row[0];
                	        mysql_free_result( Result );
        	        }
	        }

		string Aliases = "";
		string Query = "SELECT DISTINCT name, spoofedrealm FROM oh_gameplayers WHERE ip = '" + UserIP + "' ORDER BY id DESC LIMIT 4;";

		if( mysql_real_query( (MYSQL *)conn, Query.c_str( ), Query.size( ) ) != 0 )
			*error = mysql_error( (MYSQL *)conn );
		else
		{
			MYSQL_RES *Result = mysql_store_result( (MYSQL *)conn );

			if( Result )
			{
				vector<string> Row = MySQLFetchRow( Result );

				while( Row.size( ) == 2 )
				{
					Aliases += ", " + Row[0] + "@" + Row[1];
					Row = MySQLFetchRow( Result );
				}

				mysql_free_result( Result );
			}
			else
				*error = mysql_error( (MYSQL *)conn );
		}

		if( Aliases.length( ) < 3 )
			return "failed";
		else
			return "Aliases: " + Aliases.substr( 2 );
	}

	if( type == "rpp" )
	{
		string LimitString;
		if( one != 0 )
			LimitString = "Limit "+UTIL_ToString(one);

		if( EscInput.empty( ) )
		{
			string Query = "DELETE FROM oh_game_offenses WHERE player_name = '"+EscUser+"' ORDER BY id ASC "+LimitString+";";
        	        if( mysql_real_query( (MYSQL *)conn, Query.c_str( ), Query.size( ) ) != 0 )
	                        *error = mysql_error( (MYSQL *)conn );
			else
			{
				if( one != 0 )
					return "Successfully removed the first penality points["+UTIL_ToString(one)+"] from User ["+EscUser+"]";
				else
					return "Successfully removed all penality points for User ["+EscUser+"]";
			}
		}
		else
		{
			string Query = "DELETE FROM oh_game_offenses WHERE player_name = '"+EscUser+"' AND reason LIKE '%"+EscInput+"%' ORDER BY id ASC "+LimitString+";";
                        if( mysql_real_query( (MYSQL *)conn, Query.c_str( ), Query.size( ) ) != 0 )
                                *error = mysql_error( (MYSQL *)conn );
			else
			{
				if( one != 0 )
					return "Removed the first ["+UTIL_ToString(one)+"] of player ["+EscUser+"] which had ["+EscInput+"] as reason.";
				else
					return "Successfully removed all penality points for User ["+EscUser+"] for reason ["+EscInput+"]";
			}
		}
		return "failed";
	}

	return "error";
}

uint32_t MySQLPWCheck( void *conn, string *error, uint32_t botid, string user )
{
        transform( user.begin( ), user.end( ), user.begin( ), (int(*)(int))tolower );
        string EscUser = MySQLEscapeString( conn, user );
        uint32_t IsPWUser = false;
        string Query = "SELECT `user_ppwd` FROM oh_users WHERE bnet_username = '" + EscUser + "' AND `user_bnet` = '2';";

        if( mysql_real_query( (MYSQL *)conn, Query.c_str( ), Query.size( ) ) != 0 )
                *error = mysql_error( (MYSQL *)conn );
        else
        {
                MYSQL_RES *Result = mysql_store_result( (MYSQL *)conn );

                if( Result )
                {
                        vector<string> Row = MySQLFetchRow( Result );

                        if( Row.size( ) == 1 )
			{
                                string Password = Row[0];
				if( Password == "" )
					return 1;
				else
					return 2;
			}
			else
				return 0;

                        mysql_free_result( Result );
                }
                else
                        *error = mysql_error( (MYSQL *)conn );
        }

        return 0;
}

uint32_t MySQLPassCheck( void *conn, string *error, uint32_t botid, string user, string pass, uint32_t st )
{
        transform( user.begin( ), user.end( ), user.begin( ), (int(*)(int))tolower );
	transform( pass.begin( ), pass.end( ), pass.begin( ), (int(*)(int))tolower );
        string EscUser = MySQLEscapeString( conn, user );
	string EscPass = MySQLEscapeString( conn, pass );
	if( st == 0 )
	{
	        string Query = "SELECT `user_ppwd` FROM oh_users WHERE bnet_username = '" + EscUser + "' AND `user_bnet` = '2';";
        	if( mysql_real_query( (MYSQL *)conn, Query.c_str( ), Query.size( ) ) != 0 )
        	{
        	        *error = mysql_error( (MYSQL *)conn );
                	return 0;
	        }
        	else
        	{
                	MYSQL_RES *Result = mysql_store_result( (MYSQL *)conn );
	                if (Result)
        	        {
                	        vector<string> Row = MySQLFetchRow( Result );

        	                if( Row.size( ) == 1 ) {
	                                string Pass = Row[0];
					if( Pass == EscPass )
						return 1;
			 		else
                                        	return 2;
				}
        	                else
                	                return 3;

	                        mysql_free_result( Result );

        	        }
        	}
	}

	if( st == 1 )
	{
		string Query = "UPDATE `oh_users` SET `user_ppwd` = '' WHERE bnet_username = '" + EscUser + "' AND `user_bnet` = '2';";
                if( mysql_real_query( (MYSQL *)conn, Query.c_str( ), Query.size( ) ) != 0 )
                {
                        *error = mysql_error( (MYSQL *)conn );
                        return 0;
                }
                else
			return 4;
	}
	return 0;
}

uint32_t MySQLpm( void *conn, string *error, uint32_t botid, string user, string listener, uint32_t status, string message, string type )
{

        transform( user.begin( ), user.end( ), user.begin( ), (int(*)(int))tolower );
        transform( listener.begin( ), listener.end( ), listener.begin( ), (int(*)(int))tolower );
        string EscUser = MySQLEscapeString( conn, user );
        string EscListener = MySQLEscapeString( conn, listener );
        string EscMessage = MySQLEscapeString( conn, message );

	if( type == "add" )
	{
		string Query = "INSERT INTO `oh_pm` ( `m_from`, `m_to`, `m_time`, `m_read`, `m_message` ) VALUES ('" + EscUser + "', '" + EscListener + "', CURRENT_TIMESTAMP( ), '0', '" + EscMessage + "' );";
	        if( mysql_real_query( (MYSQL *)conn, Query.c_str( ), Query.size( ) ) != 0 )
	                *error = mysql_error( (MYSQL *)conn );
	        else
			return -1;
	}
	if( type == "join" )
	{
		string Query2 = "SELECT COUNT(*) FROM `oh_pm` WHERE `m_to` = '" + EscUser + "' AND `m_read` = '0';";
	        if( mysql_real_query( (MYSQL *)conn, Query2.c_str( ), Query2.size( ) ) != 0 )
	                *error = mysql_error( (MYSQL *)conn );
	        else
	        {
	                MYSQL_RES *Result = mysql_store_result( (MYSQL *)conn );

	                if( Result )
	                {
	                        vector<string> Row = MySQLFetchRow( Result );

                        	while( !Row.empty( ) )
                        	{
                                	 return UTIL_ToUInt32( Row[0] );
                        	}

                        	mysql_free_result( Result );
                	}
        	        else
	                        *error = mysql_error( (MYSQL *)conn );

		}

        }
	return 0;
}

vector<string> MySQLPList( void *conn, string *error, uint32_t botid, string server )
{
        string EscServer = MySQLEscapeString( conn, server );
	string DeleteQuery = "UPDATE `oh_users` SET `user_level` = '0' WHERE `user_bnet` >= '1' AND `admin_realm` = '" + EscServer + "' AND `expire_date`  <= CURRENT_TIMESTAMP() AND expire_date != '' AND expire_date!='0000-00-00 00:00:00';";
        if( mysql_real_query( (MYSQL *)conn, DeleteQuery.c_str( ), DeleteQuery.size( ) ) != 0 )
                *error = mysql_error( (MYSQL *)conn );

        vector<string> PList;
        string Query = "SELECT `bnet_username`, `user_level` FROM oh_users WHERE `user_bnet` >= '1' AND `admin_realm` = '" + EscServer + "'";

        if( mysql_real_query( (MYSQL *)conn, Query.c_str( ), Query.size( ) ) != 0 )
                *error = mysql_error( (MYSQL *)conn );
        else
        {
                MYSQL_RES *Result = mysql_store_result( (MYSQL *)conn );

                if( Result )
                {
                        vector<string> Row = MySQLFetchRow( Result );

                        while( !Row.empty( ) )
                        {
                                PList.push_back( Row[0] + " " + Row[1] );
                                Row = MySQLFetchRow( Result );
                        }

                        mysql_free_result( Result );
                }
                else
                        *error = mysql_error( (MYSQL *)conn );
        }
        return PList;
}

vector<string> MySQLFlameList( void *conn, string *error, uint32_t botid )
{
        vector<string> FlameList;
        string Query = "SELECT `field_value` FROM `oh_custom_fields` WHERE `field_id` = '1' AND	`field_name` = 'oh_badwords'";

        if( mysql_real_query( (MYSQL *)conn, Query.c_str( ), Query.size( ) ) != 0 )
                *error = mysql_error( (MYSQL *)conn );
        else
        {
                MYSQL_RES *Result = mysql_store_result( (MYSQL *)conn );

                if( Result )
                {
                        vector<string> Row = MySQLFetchRow( Result );

                        if( !Row.empty( ) )
                        {
				string Word;
				stringstream SS;
				SS << Row[0];
				while( SS >> Word )
				{
        	                        FlameList.push_back( Word );
				}
                        }

                        mysql_free_result( Result );
                }
                else
                        *error = mysql_error( (MYSQL *)conn );
        }

        return FlameList;
}

vector<string> MySQLAnnounceList( void *conn, string *error, uint32_t botid )
{
        vector<string> AnnounceList;
        string Query = "SELECT `field_value` FROM `oh_custom_fields` WHERE `field_id` = '1' AND `field_name` = 'oh_announcements'";

        if( mysql_real_query( (MYSQL *)conn, Query.c_str( ), Query.size( ) ) != 0 )
                *error = mysql_error( (MYSQL *)conn );
        else
        {
                MYSQL_RES *Result = mysql_store_result( (MYSQL *)conn );

                if( Result )
                {
                        vector<string> Row = MySQLFetchRow( Result );

                        if( !Row.empty( ) )
                        {
				const char *row = Row[0].c_str();
  				char delims = '\n';
  				std::stringstream ss(row);
  				std::string to;

  				if( row != NULL )
  				{
    					while(std::getline(ss,to,'\n'))
					{
     						AnnounceList.push_back( to );
    					}
  				}
                        }

                        mysql_free_result( Result );
                }
                else
                        *error = mysql_error( (MYSQL *)conn );
        }

        return AnnounceList;
}

vector<string> MySQLDCountryList( void *conn, string *error, uint32_t botid )
{
        vector<string> DCountryList;
        string Query = "SELECT `field_value` FROM `oh_custom_fields` WHERE `field_id` = '1' AND `field_name` = 'oh_country_ban'";

        if( mysql_real_query( (MYSQL *)conn, Query.c_str( ), Query.size( ) ) != 0 )
                *error = mysql_error( (MYSQL *)conn );
        else
        {
                MYSQL_RES *Result = mysql_store_result( (MYSQL *)conn );

                if( Result )
                {
                        vector<string> Row = MySQLFetchRow( Result );

                        if( !Row.empty( ) )
                        {
                                string CC;
                                stringstream SS;
                                SS << Row[0];
                                while( SS >> CC )
                                {
                                        DCountryList.push_back( CC );
                                }
                        }

                        mysql_free_result( Result );
                }
                else
                        *error = mysql_error( (MYSQL *)conn );
        }

        return DCountryList;
}

uint32_t MySQLStoreLog( void *conn, string *error, uint32_t botid, uint32_t chatid, string game, vector<string> admin )
{
        uint32_t RowID = 0;
        string EscGameInfo = MySQLEscapeString( conn, game );

	if( EscGameInfo != "" ) {
		string GIQuery = "INSERT INTO oh_game_log ( `botid`, `gameid`, `log_time`, `log_data` ) VALUES ( '" + UTIL_ToString( botid ) + "', '" + UTIL_ToString( chatid ) + "', CURRENT_TIMESTAMP(), '" + EscGameInfo + "' );";
       		if( mysql_real_query( (MYSQL *)conn, GIQuery.c_str( ), GIQuery.size( ) ) != 0 )
                	*error = mysql_error( (MYSQL *)conn );
	        else
        	        RowID = mysql_insert_id( (MYSQL *)conn );
	}

      	for( vector<string> :: iterator i = admin.begin( ); i != admin.end( ); ++i )
       	{
               	string Admin;
               	string Rest;
               	stringstream SS;
               	SS << *i;
               	SS >> Admin;
                if( !SS.eof( ) )
                {
                        getline( SS, Rest );
                        string :: size_type Start = Rest.find_first_not_of( " " );

                	if( Start != string :: npos )
		                Rest = Rest.substr( Start );
                }

		string EscAdmin = MySQLEscapeString( conn, Admin );
		string EscRest = MySQLEscapeString( conn, Rest );
		if( EscAdmin != "" && EscRest != "" )
		{
                	string GIQuery = "INSERT INTO oh_adminlog ( `botid`, `gameid`, `log_time`, `log_admin`, `log_data` ) VALUES ( '" + UTIL_ToString( botid ) + "', '" + UTIL_ToString( chatid ) + "', CURRENT_TIMESTAMP(),'" + EscAdmin + "',  '" + EscRest + "' );";
       	        	if( mysql_real_query( (MYSQL *)conn, GIQuery.c_str( ), GIQuery.size( ) ) != 0 )
               	        	*error = mysql_error( (MYSQL *)conn );
               		else
                       		RowID = mysql_insert_id( (MYSQL *)conn );
		}
        }

        return RowID;
}

uint32_t MySQLgs( void *conn, string *error, uint32_t botid, uint32_t chatid, string gn, uint32_t st, uint32_t gametype )
{
        uint32_t RowID = 0;
        string EscGN = MySQLEscapeString( conn, gn );
        if( st == 1 ) {
                string CRQuery = "INSERT INTO oh_game_status ( `botid`, `gameid`, `gamestatus`, `gamename`, `gametime`, `gametype` ) VALUES ( '" + UTIL_ToString( botid ) + "', '" + UTIL_ToString( chatid ) + "', 1, '" + EscGN + "', CURRENT_TIMESTAMP( ), '" + UTIL_ToString( gametype ) + "'  );";
                if( mysql_real_query( (MYSQL *)conn, CRQuery.c_str( ), CRQuery.size( ) ) != 0 )
                        *error = mysql_error( (MYSQL *)conn );
                else
                        RowID = mysql_insert_id( (MYSQL *)conn );
        } else if( st == 2 ) {
                string UQuery = "UPDATE oh_game_status SET `gamestatus`='" + UTIL_ToString( st ) + "', `gametime` = CURRENT_TIMESTAMP( ) WHERE `gameid` = '" + UTIL_ToString( chatid ) + "' AND `botid` = '" + UTIL_ToString( botid ) + "';";
                if( mysql_real_query( (MYSQL *)conn, UQuery.c_str( ), UQuery.size( ) ) != 0 )
                        *error = mysql_error( (MYSQL *)conn );
                else
                        RowID = mysql_insert_id( (MYSQL *)conn );
        } else {
                string DQuery = "DELETE FROM oh_game_status WHERE `gameid` = '" + UTIL_ToString( chatid ) + "' AND `botid` = '" + UTIL_ToString( botid ) + "';";
                if( mysql_real_query( (MYSQL *)conn, DQuery.c_str( ), DQuery.size( ) ) != 0 )
                        *error = mysql_error( (MYSQL *)conn );
                else
                        RowID = mysql_insert_id( (MYSQL *)conn );
        }
        return RowID;
}

uint32_t MySQLpenp( void *conn, string *error, uint32_t botid, string name, string reason, string admin, uint32_t amount, string type )
{
        uint32_t Result = 0;
	transform( name.begin( ), name.end( ), name.begin( ), (int(*)(int))tolower );
	string EscName = MySQLEscapeString( conn, name );
        string EscReason = MySQLEscapeString( conn, reason );
        string EscAdmin = MySQLEscapeString( conn, admin );
	uint32_t RecentPP = 0;
	string CheckQuery = "SELECT SUM(pp) FROM `oh_game_offenses` WHERE `player_name` = '" + EscName + "';";
        if( mysql_real_query( (MYSQL *)conn, CheckQuery.c_str( ), CheckQuery.size( ) ) != 0 )
        	*error = mysql_error( (MYSQL *)conn );
        else
        {
                MYSQL_RES *Result = mysql_store_result( (MYSQL *)conn );

                if( Result )
                {
                        vector<string> Row = MySQLFetchRow( Result );

                        while( Row.size( ) == 1 )
                        {
				if( !Row[0].empty() )
	        	                RecentPP += UTIL_ToUInt32( Row[0] );

				Row = MySQLFetchRow( Result );
	                }
                	mysql_free_result( Result );
                }
        	else
        		*error = mysql_error( (MYSQL *)conn );
	}

	if( type == "check" )
		return RecentPP;

	else if( type == "add" )
	{
		string AddQuery = "INSERT INTO `oh_game_offenses` ( player_name, reason, offence_time, pp, admin ) VALUES ( '" + EscName + "', '" + EscReason + "', CURRENT_TIMESTAMP(), '" + UTIL_ToString( amount ) + "', '" + EscAdmin + "' ); ";
		string StatsQ = "UPDATE `oh_stats` SET `penalty`='penalty+1' WHERE `player_lower` = '" + EscName + "';";
                if( mysql_real_query( (MYSQL *)conn, StatsQ.c_str( ), StatsQ.size( ) ) != 0 )
                        *error = mysql_error( (MYSQL *)conn );

                if( mysql_real_query( (MYSQL *)conn, AddQuery.c_str( ), AddQuery.size( ) ) != 0 )
                        *error = mysql_error( (MYSQL *)conn );
                else
		{
			uint32_t banamount = 1;
			if ( RecentPP<5 && RecentPP + amount >=5)
 				banamount = 86400*7;
			if ( RecentPP<10 && RecentPP + amount >=10)
				banamount = 86400*14;
			if ( RecentPP<15 && RecentPP + amount >=15)
				banamount = 86400*30;
			if ( RecentPP<20 && RecentPP + amount >=20)
				banamount = 0;

			if( banamount != 1 )
			{
				string AddBan = MySQLBanAdd( (MYSQL *)conn, error, botid, "", EscName, "", "", EscAdmin, "Too many penalty points", banamount, "" );
				return 2;
			}
			else
				return 1;
		}
	}
	return 0;
}

uint32_t MySQLBanCount( void *conn, string *error, uint32_t botid, string server )
{
	string EscServer = MySQLEscapeString( conn, server );
	uint32_t Count = 0;
	string Query = "SELECT COUNT(*) FROM oh_bans WHERE server='" + EscServer + "'";

	if( mysql_real_query( (MYSQL *)conn, Query.c_str( ), Query.size( ) ) != 0 )
		*error = mysql_error( (MYSQL *)conn );
	else
	{
		MYSQL_RES *Result = mysql_store_result( (MYSQL *)conn );

		if( Result )
		{
			vector<string> Row = MySQLFetchRow( Result );

			if( Row.size( ) == 1 )
				Count = UTIL_ToUInt32( Row[0] );
			else
				*error = "error counting bans [" + server + "] - row doesn't have 1 column";

			mysql_free_result( Result );
		}
		else
			*error = mysql_error( (MYSQL *)conn );
	}
	return Count;
}

CDBBan *MySQLBanCheck( void *conn, string *error, uint32_t botid, string server, string user, string ip )
{
	transform( user.begin( ), user.end( ), user.begin( ), (int(*)(int))tolower );
	string EscServer = MySQLEscapeString( conn, server );
	string EscUser = MySQLEscapeString( conn, user );
	string EscIP = MySQLEscapeString( conn, ip );
	CDBBan *Ban = NULL;
	string Query;

	if( ip.empty( ) )
		Query = "SELECT name, ip, DATE(date), gamename, admin, reason, DATE(expiredate), TIMESTAMPDIFF(WEEK, NOW( ), expiredate) AS MONTH, TIMESTAMPDIFF(DAY, NOW( ), expiredate)-TIMESTAMPDIFF(WEEK, NOW( ), expiredate)*7 AS DAY, TIMESTAMPDIFF(HOUR ,NOW( ) ,expiredate)-TIMESTAMPDIFF(DAY, NOW( ),  expiredate)*24 AS HOUR, TIMESTAMPDIFF(MINUTE, NOW( ), expiredate)-TIMESTAMPDIFF(HOUR ,NOW( ) ,expiredate) *60 AS MINUTE FROM oh_bans WHERE name='" + EscUser + "' AND expiredate = '' OR expiredate='0000-00-00 00:00:00' OR expiredate>CURRENT_TIMESTAMP()";
	else
		Query = "SELECT name, ip, DATE(date), gamename, admin, reason, DATE(expiredate), TIMESTAMPDIFF(WEEK, NOW( ), expiredate) AS MONTH, TIMESTAMPDIFF(DAY, NOW( ), expiredate)-TIMESTAMPDIFF(WEEK, NOW( ), expiredate)*7 AS DAY, TIMESTAMPDIFF(HOUR ,NOW( ) ,expiredate)-TIMESTAMPDIFF(DAY, NOW( ),  expiredate)*24 AS HOUR, TIMESTAMPDIFF(MINUTE, NOW( ), expiredate)-TIMESTAMPDIFF(HOUR ,NOW( ) ,expiredate) *60 AS MINUTE FROM oh_bans WHERE name='" + EscUser + "' OR ip='" + EscIP + "' AND expiredate = '' OR expiredate='0000-00-00 00:00:00' OR expiredate>CURRENT_TIMESTAMP()";

	if( mysql_real_query( (MYSQL *)conn, Query.c_str( ), Query.size( ) ) != 0 )
		*error = mysql_error( (MYSQL *)conn );
	else
	{
		MYSQL_RES *Result = mysql_store_result( (MYSQL *)conn );

		if( Result )
		{
			vector<string> Row = MySQLFetchRow( Result );

			if( Row.size( ) == 11 )
				Ban = new CDBBan( server, Row[0], Row[1], Row[2], Row[3], Row[4], Row[5], Row[6], Row[7], Row[8], Row[9], Row[10] );
			/* else
				*error = "error checking ban [" + server + " : " + user + "] - row doesn't have 6 columns"; */

			mysql_free_result( Result );
		}
		else
			*error = mysql_error( (MYSQL *)conn );
	}

	return Ban;
}

string MySQLBanCheck2( void *conn, string *error, uint32_t botid, string server, string user, string type )
{
        transform( user.begin( ), user.end( ), user.begin( ), (int(*)(int))tolower );
        string EscUser = MySQLEscapeString( conn, user );
	string GetIP = "SELECT `ip` FROM `oh_gameplayers` WHERE name = '" + EscUser + "' AND `ip` != '0' AND `ip` != '0.0.0.0' ORDER BY `id` DESC;";
	string UserIP = "";
        if( mysql_real_query( (MYSQL *)conn, GetIP.c_str( ), GetIP.size( ) ) != 0 )
                *error = mysql_error( (MYSQL *)conn );
        else
        {
                MYSQL_RES *Result = mysql_store_result( (MYSQL *)conn );
                if( Result )
                {
                        vector<string> Row = MySQLFetchRow( Result );
                	if( Row.size( ) == 1 )
                		UserIP = Row[0];
	                mysql_free_result( Result );
                }
	}
	if( !UserIP.empty() )
	{
		uint32_t count = 0;
		std::vector<std::string> x = split2(UserIP, '.');
		string CheckIPRange = "SELECT `name` FROM `oh_bans` WHERE `ip_part` = '" + x[0] + "." + x[1] + "' GROUP BY `name`;";
		string AllNames = "";
		if( mysql_real_query( (MYSQL *)conn, CheckIPRange.c_str( ), CheckIPRange.size( ) ) != 0 )
        	        *error = mysql_error( (MYSQL *)conn );
	        else
        	{
                	MYSQL_RES *Result = mysql_store_result( (MYSQL *)conn );

	                if( Result )
        	        {
                	        vector<string> Row = MySQLFetchRow( Result );

	                        while( Row.size( ) == 1 )
        	                {
                	                if( AllNames.empty() )
						AllNames = Row[0];
					else
						AllNames += ", " + Row[0];
					count += 1;
        	                        Row = MySQLFetchRow( Result );
                	        }

	                        mysql_free_result( Result );
        	        }
                	else
                        	*error = mysql_error( (MYSQL *)conn );
	        }
		if( type == "joincheck" )
			return UTIL_ToString( count );

		if( !AllNames.empty() )
			return AllNames;
	}
        if( UserIP.empty() )
                return "norec";

	return "fail";
}

string MySQLBanAdd( void *conn, string *error, uint32_t botid, string server, string user, string ip, string gamename, string admin, string reason, uint32_t bantime, string country )
{
	string EscServer = MySQLEscapeString( conn, server );
	string EscUser = MySQLEscapeString( conn, user );
	string EscIP = MySQLEscapeString( conn, ip );
	string EscGameName = MySQLEscapeString( conn, gamename );
	string EscAdmin = MySQLEscapeString( conn, admin );
	string EscReason = MySQLEscapeString( conn, reason );
	string EscCountry = MySQLEscapeString( conn, country );
	transform( EscUser.begin( ), EscUser.end( ), EscUser.begin( ), (int(*)(int))tolower );

	bool alreadybanned = false;
	uint32_t currentbantime = 0;
	string CheckQuery = "SELECT UNIX_TIMESTAMP( expiredate )-UNIX_TIMESTAMP() as currentbantime FROM oh_bans WHERE name = '" + EscUser + "';";
        if( mysql_real_query( (MYSQL *)conn, CheckQuery.c_str( ), CheckQuery.size( ) ) != 0 )
        	*error = mysql_error( (MYSQL *)conn );
        else
        {
                MYSQL_RES *Result = mysql_store_result( (MYSQL *)conn );
                if( Result )
                {
                        vector<string> Row = MySQLFetchRow( Result );
                        if( Row.size( ) == 1 )
			{
				currentbantime = UTIL_ToUInt32( Row[0] );
				if( currentbantime > 20000000 )
	                		return "alreadypermbanned";
				else if ( currentbantime > bantime && bantime != 0 )
					return "alreadybannedwithhigheramount";
			}

       		        mysql_free_result( Result );
 	        }
        }

        if( ip.empty( ) || server.empty( ) )
	{
		EscIP = "0.0.0.0";
		string IPQuery = "SELECT `ip`,`spoofedrealm` FROM `oh_gameplayers` WHERE `name` = '" + EscUser + "' ORDER BY `id` DESC LIMIT 1;";
	        if( mysql_real_query( (MYSQL *)conn, IPQuery.c_str( ), IPQuery.size( ) ) != 0 )
        	        *error = mysql_error( (MYSQL *)conn );
	        else
        	{
	                MYSQL_RES *Result = mysql_store_result( (MYSQL *)conn );
                	if( Result )
        	        {
	                        vector<string> Row = MySQLFetchRow( Result );
	                        if( Row.size( ) == 2 )
				{
					EscIP = Row[0];
					EscServer = Row[1];
				}
	                        mysql_free_result( Result );
			}
                }
        }

        if( EscAdmin == "AutoBan" )
        {
                uint32_t RecentLeaves = 0;
                string CheckRecentLeaves = "SELECT leaver FROM stats WHERE `player_lower` = '" + EscUser + "';";
                if( mysql_real_query( (MYSQL *)conn, CheckRecentLeaves.c_str( ), CheckRecentLeaves.size( ) ) != 0 )
                        *error = mysql_error( (MYSQL *)conn );
                else
                {
                        MYSQL_RES *Result = mysql_store_result( (MYSQL *)conn );
                        if( Result )
                        {
                                vector<string> Row = MySQLFetchRow( Result );
                                if( Row.size( ) == 1 )
                                        RecentLeaves = UTIL_ToUInt32( Row[0] );

                                mysql_free_result( Result );
                        }
                }
                if( RecentLeaves != 0 )
			bantime = 1+(bantime*2);
	}

	bool Success = false;
	std::vector<std::string> x = split2(EscIP, '.');

	if( country.empty( ) )
        {
		string FromQuery = "SELECT code FROM  `oh_geoip` WHERE INET_ATON('"+EscIP+"') BETWEEN ip_start_int AND ip_end_int";
                if( mysql_real_query( (MYSQL *)conn, FromQuery.c_str( ), FromQuery.size( ) ) != 0 )
                        *error = mysql_error( (MYSQL *)conn );
                else
                {
                        MYSQL_RES *Result = mysql_store_result( (MYSQL *)conn );
                        if( Result )
                        {
                                vector<string> Row = MySQLFetchRow( Result );
                                if( Row.size( ) == 1 )
                                        EscCountry = Row[0];

                                mysql_free_result( Result );
                        }
                }
        }

	if( currentbantime == 0 )
	{
		string Query = "";
		string OffenseQuery = "";
		if( bantime == 0 )
		{
			Query = "INSERT INTO oh_bans ( botid, server, name, ip, ip_part, date, gamename, admin, reason, country ) VALUES ( " + UTIL_ToString( botid ) + ", '" + EscServer + "', '" + EscUser + "', '" + EscIP + "', '" + x[0] + "." + x[1] + "', CURRENT_TIMESTAMP( ), '" + EscGameName + "', '" + EscAdmin + "', '" + EscReason + "', '" + EscCountry + "' )";
			OffenseQuery = "INSERT INTO oh_game_offenses ( player_name, reason, offence_time, pp, admin ) VALUES ( '" + EscUser + "', '" + EscReason + "', CURRENT_TIMESTAMP( ), 1, '" + EscAdmin + "' );";
		}
		else
		{
			Query = "INSERT INTO oh_bans ( botid, server, name, ip, ip_part,date, gamename, admin, reason, expiredate, country ) VALUES ( " + UTIL_ToString( botid ) + ", '" + EscServer + "', '" + EscUser + "', '" + EscIP + "', '" + x[0] + "." + x[1] + "', CURRENT_TIMESTAMP( ), '" + EscGameName + "', '" + EscAdmin + "', '" + EscReason + "', FROM_UNIXTIME( UNIX_TIMESTAMP( ) + " + UTIL_ToString(bantime) + "), '" + EscCountry + "' );";
                	OffenseQuery = "INSERT INTO oh_game_offenses ( player_name, reason, offence_time, pp, admin, offence_expire ) VALUES ( '" + EscUser + "', '" + EscReason + "', CURRENT_TIMESTAMP( ), 1, '" + EscAdmin + "', FROM_UNIXTIME( UNIX_TIMESTAMP( ) + " + UTIL_ToString(bantime) + ") );";
		}

		if( mysql_real_query( (MYSQL *)conn, Query.c_str( ), Query.size( ) ) != 0 )
			*error = mysql_error( (MYSQL *)conn );
		else
			Success = true;

		if( EscReason != "Too many penalty points" );
		{
	        	if( mysql_real_query( (MYSQL *)conn, OffenseQuery.c_str( ), OffenseQuery.size( ) ) != 0 )
        	        	*error = mysql_error( (MYSQL *)conn );
		}

		return "Successfully banned User ["+user+"] on ["+EscServer+"] for ["+EscReason+"]";
	}
	else
	{
		string Query = "";
		string OffenseQuery = "";
		if( bantime == 0 )
		{
			Query = "UPDATE oh_bans SET expiredate = '0000-00-00 00:00:00', reason = '"+EscReason+"' WHERE name = '"+EscUser+"';";
			OffenseQuery = "INSERT INTO oh_game_offenses ( player_name, reason, offence_time, pp, admin ) VALUES ( '" + EscUser + "', '" + EscReason + "', CURRENT_TIMESTAMP( ), 1, '" + EscAdmin + "' );";
		}
		else
		{
			Query = "UPDATE oh_bans SET expiredate = FROM_UNIXTIME( UNIX_TIMESTAMP( ) + " + UTIL_ToString(bantime) + "), reason = '" + EscReason + "' WHERE name = '"+EscUser+"';";
			OffenseQuery = "INSERT INTO oh_game_offenses ( player_name, reason, offence_time, pp, admin, offence_expire ) VALUES ( '" + EscUser + "', '" + EscReason + "', CURRENT_TIMESTAMP( ), 1, '" + EscAdmin + "', FROM_UNIXTIME( UNIX_TIMESTAMP( ) + " + UTIL_ToString(bantime) + ") );";
		}

                if( mysql_real_query( (MYSQL *)conn, Query.c_str( ), Query.size( ) ) != 0 )
                        *error = mysql_error( (MYSQL *)conn );
                else
                        Success = true;

                if( EscReason != "Too many penalty points" );
                {
                        if( mysql_real_query( (MYSQL *)conn, OffenseQuery.c_str( ), OffenseQuery.size( ) ) != 0 )
                                *error = mysql_error( (MYSQL *)conn );
                }
		if( bantime == 0 )
			return "Updated User's ban ["+user+"] to a permanent ban.";
		else
	                return "Successfully updated User's ban ["+user+"] on ["+EscServer+"] for ["+EscReason+"]";
	}

	return "failed";
}

bool MySQLPUp( void *conn, string *error, uint32_t botid, string name, uint32_t level, string realm, string user )
{
	bool Success = false;
        transform( user.begin( ), user.end( ), user.begin( ), (int(*)(int))tolower );
	transform( name.begin( ), name.end( ), name.begin( ), (int(*)(int))tolower );
	string EscName = MySQLEscapeString( conn, name );
        string EscRealm = MySQLEscapeString( conn, realm );
        string EscUser = MySQLEscapeString( conn, user );
	uint32_t time = 31120000;
	if( level == 6 || level == 5 )
		time = 15551000;
	if( level == 3 || level == 2 )
		time = 2592000;

	string CQuery = "SELECT `user_level` from `oh_users` WHERE `bnet_username` = '" + EscName + "' AND `admin_realm` = '" + EscRealm + "';";
        if( mysql_real_query( (MYSQL *)conn, CQuery.c_str( ), CQuery.size( ) ) != 0 )
                *error = mysql_error( (MYSQL *)conn );
        else
	{
                MYSQL_RES *Result = mysql_store_result( (MYSQL *)conn );

                if( Result )
                {
                        vector<string> Row = MySQLFetchRow( Result );

                        if( Row.size( ) == 0 )
				return false;

                        mysql_free_result( Result );
                }
	}

	string Query = "UPDATE `oh_users` SET `user_level` = '" + UTIL_ToString( level ) + "', `expire_date` = 'FROM_UNIXTIME( UNIX_TIMESTAMP( ) + " + UTIL_ToString(time) + ")' WHERE `bnet_username` = '" + EscName + "' AND `admin_realm` = '" + EscRealm + "';";

	if( mysql_real_query( (MYSQL *)conn, Query.c_str( ), Query.size( ) ) != 0 )
                *error = mysql_error( (MYSQL *)conn );
        else
                Success = true;

        return Success;

}

bool MySQLBanRemove( void *conn, string *error, uint32_t botid, string server, string user )
{
	transform( user.begin( ), user.end( ), user.begin( ), (int(*)(int))tolower );
	string EscServer = MySQLEscapeString( conn, server );
	string EscUser = MySQLEscapeString( conn, user );
	bool Success = false;
	string Query = "UPDATE oh_bans SET `expiredate` = CURRENT_TIMESTAMP WHERE server='" + EscServer + "' AND name='" + EscUser + "'";

	if( mysql_real_query( (MYSQL *)conn, Query.c_str( ), Query.size( ) ) != 0 )
		*error = mysql_error( (MYSQL *)conn );
	else
		Success = true;

	return Success;
}

bool MySQLBanRemove( void *conn, string *error, uint32_t botid, string user )
{
	transform( user.begin( ), user.end( ), user.begin( ), (int(*)(int))tolower );
	string EscUser = MySQLEscapeString( conn, user );
	bool Success = false;
	string Query = "DELETE FROM oh_bans WHERE name='" + EscUser + "' ORDER BY id ASC LIMIT 1";

	if( mysql_real_query( (MYSQL *)conn, Query.c_str( ), Query.size( ) ) != 0 )
		*error = mysql_error( (MYSQL *)conn );
	else
		Success = true;

	return Success;
}

bool MySQLTBRemove( void *conn, string *error, uint32_t botid, string server )
{
        bool Success = false;
        string Query = "DELETE FROM oh_bans WHERE expiredate <= CURRENT_TIMESTAMP() AND expiredate != '' AND expiredate!='0000-00-00 00:00:00'";

        if( mysql_real_query( (MYSQL *)conn, Query.c_str( ), Query.size( ) ) != 0 )
                *error = mysql_error( (MYSQL *)conn );
        else
                Success = true;

        return Success;
}

vector<CDBBan *> MySQLBanList( void *conn, string *error, uint32_t botid, string server )
{
	string EscServer = MySQLEscapeString( conn, server );
	vector<CDBBan *> BanList;
	string Query = "SELECT name, ip, DATE(date), gamename, admin, reason, DATE(expiredate), TIMESTAMPDIFF(WEEK, NOW( ), expiredate) AS MONTH, TIMESTAMPDIFF(DAY, NOW( ), expiredate)-TIMESTAMPDIFF(WEEK, NOW( ), expiredate)*7 AS DAY, TIMESTAMPDIFF(HOUR ,NOW( ) ,expiredate)-TIMESTAMPDIFF(DAY, NOW( ),  expiredate)*24 AS HOUR, TIMESTAMPDIFF(MINUTE, NOW( ), expiredate)-TIMESTAMPDIFF(HOUR ,NOW( ) ,expiredate) *60 AS MINUTE FROM oh_bans WHERE server='" + EscServer + "' AND expiredate = '' OR expiredate='0000-00-00 00:00:00' OR expiredate>CURRENT_TIMESTAMP()";

	if( mysql_real_query( (MYSQL *)conn, Query.c_str( ), Query.size( ) ) != 0 )
		*error = mysql_error( (MYSQL *)conn );
	else
	{
		MYSQL_RES *Result = mysql_store_result( (MYSQL *)conn );

		if( Result )
		{
			vector<string> Row = MySQLFetchRow( Result );

			while( Row.size( ) == 11 )
			{
				BanList.push_back( new CDBBan( server, Row[0], Row[1], Row[2], Row[3], Row[4], Row[5], Row[6], Row[7], Row[8], Row[9], Row[10] ) );
				Row = MySQLFetchRow( Result );
			}

			mysql_free_result( Result );
		}
		else
			*error = mysql_error( (MYSQL *)conn );
	}

	return BanList;
}

vector<string> MySQLCommandList( void *conn, string *error, uint32_t botid )
{
	vector<string> CommandList;
	string Query = "SELECT command FROM oh_commands WHERE botid='" + UTIL_ToString(botid) + "' OR  botid='0'";

	if( mysql_real_query( (MYSQL *)conn, Query.c_str( ), Query.size( ) ) != 0 )
		*error = mysql_error( (MYSQL *)conn );
	else
	{
		MYSQL_RES *Result = mysql_store_result( (MYSQL *)conn );

		if( Result )
		{
			vector<string> Row = MySQLFetchRow( Result );

			while( Row.size( ) == 1 )
			{
				CommandList.push_back( Row[0] );
				Row = MySQLFetchRow( Result );
			}

			mysql_free_result( Result );
		}
		else
			*error = mysql_error( (MYSQL *)conn );
	}

	string DeleteQuery = "DELETE FROM oh_commands WHERE botid='" + UTIL_ToString(botid) + "'";

	if( mysql_real_query( (MYSQL *)conn, DeleteQuery.c_str( ), DeleteQuery.size( ) ) != 0 )
		*error = mysql_error( (MYSQL *)conn );

	return CommandList;
}

uint32_t MySQLGameAdd( void *conn, string *error, uint32_t botid, string server, string map, string gamename, string ownername, uint32_t duration, uint32_t gamestate, string creatorname, string creatorserver, uint32_t gametype, vector<string> lobbylog, vector<string> gamelog )
{
	uint32_t RowID = 0;
	string EscServer = MySQLEscapeString( conn, server );
	string EscMap = MySQLEscapeString( conn, map );
	string EscGameName = MySQLEscapeString( conn, gamename );
	string EscOwnerName = MySQLEscapeString( conn, ownername );
	string EscCreatorName = MySQLEscapeString( conn, creatorname );
	string EscCreatorServer = MySQLEscapeString( conn, creatorserver );
	string Query = 	"INSERT INTO oh_games ( botid, server, map, datetime, gamename, ownername, duration, gamestate, gametype, creatorname, creatorserver ) VALUES ( " + UTIL_ToString( botid ) + ", '" + EscServer + "', '" + EscMap + "', NOW( ), '" + EscGameName + "', '" + EscOwnerName + "', " + UTIL_ToString( duration ) + ", " + UTIL_ToString( gamestate ) + ", " + UTIL_ToString( gametype ) + ", '" + EscCreatorName + "', '" + EscCreatorServer + "' )";
	if( mysql_real_query( (MYSQL *)conn, Query.c_str( ), Query.size( ) ) != 0 )
		*error = mysql_error( (MYSQL *)conn );
	else
		 RowID = mysql_insert_id( (MYSQL *)conn );;

        string GIQuery = "INSERT INTO oh_game_info ( botid, gameid, server, map, datetime, gamename, ownername, duration, gamestate, gametype, creatorname, creatorserver ) VALUES ( " + UTIL_ToString( botid ) + ", '" + UTIL_ToString( RowID ) + "', '" + EscServer + "', '" + EscMap + "', NOW( ), '" + EscGameName + "', '" + EscOwnerName + "', " + UTIL_ToString( duration ) + ", " + UTIL_ToString( gamestate ) + ", " + UTIL_ToString( gametype ) + ", '" + EscCreatorName + "', '" + EscCreatorServer + "' )";
        if( mysql_real_query( (MYSQL *)conn, GIQuery.c_str( ), GIQuery.size( ) ) != 0 )
                *error = mysql_error( (MYSQL *)conn );
/*
	if( RowID != 0 )
	{
		string LobbyLog;
		for( vector<string> :: iterator i = lobbylog.begin( ); i != lobbylog.end( ); i++ )
			LobbyLog.append( (*i) + '\n' );

		string EscLobbyLog = MySQLEscapeString( conn, LobbyLog );

		string GameLog;
		for( vector<string> :: iterator i = gamelog.begin( ); i != gamelog.end( ); i++ )
			GameLog.append( (*i) + '\n' );

		string EscGameLog = MySQLEscapeString( conn, GameLog );

		string InsertQ = "INSERT INTO oh_lobby_game_logs ( gameid, botid, gametype, lobbylog, gamelog ) VALUES ( "+UTIL_ToString(RowID)+", "+UTIL_ToString(botid)+", "+UTIL_ToString(gametype)+", '"+EscLobbyLog+"', '"+EscGameLog+"' )";

	        if( mysql_real_query( (MYSQL *)conn, InsertQ.c_str( ), InsertQ.size( ) ) != 0 )
        	        *error = mysql_error( (MYSQL *)conn );
	}
*/
	return RowID;
}

string MySQLGameUpdate( void *conn, string *error, uint32_t botid, string map, string gamename, string ownername, string creatorname, uint32_t players, string usernames, uint32_t slotsTotal, uint32_t totalGames, uint32_t totalPlayers, bool add )
{
	if(add) {
        string EscMap = MySQLEscapeString(conn, map);
        string EscGameName = MySQLEscapeString( conn, gamename );
        string EscOwnerName = MySQLEscapeString( conn, ownername );
        string EscCreatorName = MySQLEscapeString( conn, creatorname );
        string EscUsernames = MySQLEscapeString( conn, usernames );
        string Query = "UPDATE oh_gamelist SET map = '" + EscMap + "', gamename = '" + EscGameName + "', ownername = '" + EscOwnerName + "', creatorname = '" + EscCreatorName + "', slotstaken = '" + UTIL_ToString(players) + "', slotstotal = '" + UTIL_ToString(slotsTotal) + "', usernames = '" + EscUsernames + "', totalgames = '" + UTIL_ToString(totalGames) + "', totalplayers = '" + UTIL_ToString(totalPlayers) + "' WHERE botid='" + UTIL_ToString(botid) + "'";

        if( mysql_real_query( (MYSQL *)conn, Query.c_str( ), Query.size( ) ) != 0 )
            *error = mysql_error( (MYSQL *)conn );

        return "";
    } else {
        string Query = "SELECT gamename,slotstaken,slotstotal,totalgames,totalplayers FROM oh_gamelist";

        if( mysql_real_query( (MYSQL *)conn, Query.c_str( ), Query.size( ) ) != 0 )
            *error = mysql_error( (MYSQL *)conn );
        else
            {
                MYSQL_RES *Result = mysql_store_result( (MYSQL *)conn );
		string response;
                int num = 0;
		int totg = 0;
		int totp = 0;
                if( Result )
                    {
                        vector<string> Row = MySQLFetchRow( Result );

                        while( !Row.empty( ) )
                            {
                                if(Row[0] != "") {
				    totg += UTIL_ToUInt32( Row[3] );
				    totp += UTIL_ToUInt32( Row[4] );
                                    response += Row[0] + " (" + Row[1] + "/" + Row[2] + "), ";
                                    num++;
                                }

                                Row = MySQLFetchRow( Result );
                            }

                        mysql_free_result( Result );
                    }
                else
                    *error = mysql_error( (MYSQL *)conn );

                if(num == 0) {
                    response = "No games avaible";
                } else {
                    response = response.substr(0, response.length() - 2);
                }

                return "Current Games ["+UTIL_ToString( totg )+"|"+UTIL_ToString(totp)+"]: "+response;
            }

        return "";
    }
}

uint32_t MySQLGamePlayerAdd( void *conn, string *error, uint32_t botid, uint32_t gameid, string name, string ip, uint32_t spoofed, string spoofedrealm, uint32_t reserved, uint32_t loadingtime, uint32_t left, string leftreason, uint32_t team, uint32_t colour )
{
	string EscNameUP = MySQLEscapeString( conn, name );
	//transform( name.begin( ), name.end( ), name.begin( ), (int(*)(int))tolower );
	uint32_t RowID = 0;
	string EscName = MySQLEscapeString( conn, name );
	string EscIP = MySQLEscapeString( conn, ip );
	string EscSpoofedRealm = MySQLEscapeString( conn, spoofedrealm );
	string EscLeftReason = MySQLEscapeString( conn, leftreason );

	string Query = "INSERT INTO oh_gameplayers ( botid, gameid, name, ip, spoofed, reserved, loadingtime, `left`, leftreason, team, colour, spoofedrealm ) VALUES ( " + UTIL_ToString( botid ) + ", " + UTIL_ToString( gameid ) + ", '" + EscName + "', '" + EscIP + "', " + UTIL_ToString( spoofed ) + ", " + UTIL_ToString( reserved ) + ", " + UTIL_ToString( loadingtime ) + ", " + UTIL_ToString( left ) + ", '" + EscLeftReason + "', " + UTIL_ToString( team ) + ", " + UTIL_ToString( colour ) + ", '" + EscSpoofedRealm + "' )";

	if( mysql_real_query( (MYSQL *)conn, Query.c_str( ), Query.size( ) ) != 0 )
		*error = mysql_error( (MYSQL *)conn );
	else
		RowID = mysql_insert_id( (MYSQL *)conn );


	return RowID;
}

CDBGamePlayerSummary *MySQLGamePlayerSummaryCheck( void *conn, string *error, uint32_t botid, string name )
{
	transform( name.begin( ), name.end( ), name.begin( ), (int(*)(int))tolower );
	string EscName = MySQLEscapeString( conn, name );
	CDBGamePlayerSummary *GamePlayerSummary = NULL;
	string Query = "SELECT MIN(DATE(datetime)), MAX(DATE(datetime)), COUNT(*), MIN(loadingtime), AVG(loadingtime), MAX(loadingtime), MIN(`left`/duration)*100, AVG(`left`/duration)*100, MAX(`left`/duration)*100, MIN(duration), AVG(duration), MAX(duration) FROM oh_gameplayers LEFT JOIN oh_games ON oh_games.id=gameid WHERE name LIKE '" + EscName + "'";

	if( mysql_real_query( (MYSQL *)conn, Query.c_str( ), Query.size( ) ) != 0 )
		*error = mysql_error( (MYSQL *)conn );
	else
	{
		MYSQL_RES *Result = mysql_store_result( (MYSQL *)conn );

		if( Result )
		{
			vector<string> Row = MySQLFetchRow( Result );

			if( Row.size( ) == 12 )
			{
				string FirstGameDateTime = Row[0];
				string LastGameDateTime = Row[1];
				uint32_t TotalGames = UTIL_ToUInt32( Row[2] );
				uint32_t MinLoadingTime = UTIL_ToUInt32( Row[3] );
				uint32_t AvgLoadingTime = UTIL_ToUInt32( Row[4] );
				uint32_t MaxLoadingTime = UTIL_ToUInt32( Row[5] );
				uint32_t MinLeftPercent = UTIL_ToUInt32( Row[6] );
				uint32_t AvgLeftPercent = UTIL_ToUInt32( Row[7] );
				uint32_t MaxLeftPercent = UTIL_ToUInt32( Row[8] );
				uint32_t MinDuration = UTIL_ToUInt32( Row[9] );
				uint32_t AvgDuration = UTIL_ToUInt32( Row[10] );
				uint32_t MaxDuration = UTIL_ToUInt32( Row[11] );
				GamePlayerSummary = new CDBGamePlayerSummary( string( ), name, FirstGameDateTime, LastGameDateTime, TotalGames, MinLoadingTime, AvgLoadingTime, MaxLoadingTime, MinLeftPercent, AvgLeftPercent, MaxLeftPercent, MinDuration, AvgDuration, MaxDuration );
			}
			else
				*error = "error checking gameplayersummary [" + name + "] - row doesn't have 12 columns";

			mysql_free_result( Result );
		}
		else
			*error = mysql_error( (MYSQL *)conn );
	}

	return GamePlayerSummary;
}

CDBStatsPlayerSummary *MySQLStatsPlayerSummaryCheck( void *conn, string *error, uint32_t botid, string name )
{
        transform( name.begin( ), name.end( ), name.begin( ), (int(*)(int))tolower );
        string EscName = MySQLEscapeString( conn, name );
        CDBStatsPlayerSummary *StatsPlayerSummary = NULL;

	string Query = "SELECT `id`, `player`, `player_lower`, `score`, `games`, `wins`, `losses`, `draw`, `kills`, `deaths`, `assists`, `creeps`, `denies`, `neutrals`, `towers`, `rax`, `streak`, `maxstreak`, `losingstreak`, `maxlosingstreak`, `zerodeaths`, `realm`, `leaver`, `forced_gproxy` FROM `oh_stats` WHERE `player_lower` = '" + EscName + "';";
        if( mysql_real_query( (MYSQL *)conn, Query.c_str( ), Query.size( ) ) != 0 )
                *error = mysql_error( (MYSQL *)conn );
        else
        {
                MYSQL_RES *Result = mysql_store_result( (MYSQL *)conn );

                if( Result )
                {
                        vector<string> Row = MySQLFetchRow( Result );

                        if( Row.size( ) == 24 )
                        {
                                uint32_t id = UTIL_ToUInt32( Row[0] );
                                string player = Row[1];
                                string playerlower = Row[2];
                                double score = UTIL_ToDouble( Row[3] );
                                uint32_t games = UTIL_ToUInt32( Row[4] );
                                uint32_t wins = UTIL_ToUInt32( Row[5] );
                                uint32_t losses = UTIL_ToUInt32( Row[6] );
                                uint32_t draw = UTIL_ToUInt32( Row[7] );
                                uint32_t kills = UTIL_ToUInt32( Row[8] );
                                uint32_t deaths = UTIL_ToUInt32( Row[9] );
                                uint32_t assists = UTIL_ToUInt32( Row[10] );
                                uint32_t creeps = UTIL_ToUInt32( Row[11] );
                                uint32_t denies = UTIL_ToUInt32( Row[12] );
                                uint32_t neutrals = UTIL_ToUInt32( Row[13] );
                                uint32_t towers = UTIL_ToUInt32( Row[14] );
                                uint32_t rax = UTIL_ToUInt32( Row[15] );
                                uint32_t streak = UTIL_ToUInt32( Row[16] );
                                uint32_t maxstreak = UTIL_ToUInt32( Row[17] );
                                uint32_t losingstreak = UTIL_ToUInt32( Row[18] );
                                uint32_t maxlosingstreak = UTIL_ToUInt32( Row[19] );
                                uint32_t zerodeaths = UTIL_ToUInt32( Row[20] );
				string realm = Row[21];
				uint32_t leaves = UTIL_ToUInt32( Row[22] );
				uint32_t forcedgproxy = UTIL_ToUInt32( Row[23] );
				uint32_t allcount = 0;
				uint32_t rankcount = 0;
				if( score > 0 )
				{
					string ALLQuery = "SELECT COUNT(*) FROM oh_stats";
					string CountQuery = "SELECT COUNT(*) FROM oh_stats WHERE score > '"+UTIL_ToString(score, 0)+"';";
        				if( mysql_real_query( (MYSQL *)conn, ALLQuery.c_str( ), ALLQuery.size( ) ) != 0 )
                				*error = mysql_error( (MYSQL *)conn );
        				else
        				{
                				MYSQL_RES *Result = mysql_store_result( (MYSQL *)conn );
				                if( Result )
				                {
                        				vector<string> Row = MySQLFetchRow( Result );
				                        if( Row.size( ) == 1 )
								allcount = UTIL_ToUInt32( Row[0] );
						}
					}
                                        if( mysql_real_query( (MYSQL *)conn, CountQuery.c_str( ), CountQuery.size( ) ) != 0 )
                                                *error = mysql_error( (MYSQL *)conn );
                                        else
                                        {
                                                MYSQL_RES *Result = mysql_store_result( (MYSQL *)conn );
                                                if( Result )
                                                {
                                                        vector<string> Row = MySQLFetchRow( Result );
                                                        if( Row.size( ) == 1 )
                                                                rankcount = UTIL_ToUInt32( Row[0] );
                                                }
                                        }
				}
                                StatsPlayerSummary = new CDBStatsPlayerSummary( id, player, playerlower, score, games, wins, losses, draw, kills, deaths, assists, creeps, denies, neutrals, towers, rax, streak, maxstreak, losingstreak, maxlosingstreak, zerodeaths, realm, leaves, allcount, rankcount, forcedgproxy );
                        }
                        //else
                              //  *error = "error checking statsplayersummary [" + name + "] - row doesn't have 23 columns";

                        mysql_free_result( Result );
                }
                else
                        *error = mysql_error( (MYSQL *)conn );
        }

        return StatsPlayerSummary;
}

CDBInboxSummary *MySQLInboxSummaryCheck( void *conn, string *error, uint32_t botid, string name )
{
	string Res = string();
        transform( name.begin( ), name.end( ), name.begin( ), (int(*)(int))tolower );
        string EscName = MySQLEscapeString( conn, name );
	CDBInboxSummary *InboxSummary = NULL;
        string Query = "SELECT `id`, `m_from`, `m_message`, `m_read` FROM `oh_pm` WHERE `m_read` = '0' AND `m_to` = '" + EscName + "';";

        if( mysql_real_query( (MYSQL *)conn, Query.c_str( ), Query.size( ) ) != 0 )
                *error = mysql_error( (MYSQL *)conn );
        else
        {
                MYSQL_RES *Result = mysql_store_result( (MYSQL *)conn );

                if( Result )
                {
                        vector<string> Row = MySQLFetchRow( Result );

                        if( Row.size( ) == 4 )
                        {
                                uint32_t id = UTIL_ToUInt32( Row[0] );
                                string User = Row[1];
                                string Message = Row[2];
				uint32_t read = UTIL_ToUInt32( Row[3] );
                                InboxSummary = new CDBInboxSummary( User, Message );
				//Update readed messages from users
				if( read == 0 ) {
					string Query2 = "UPDATE `oh_pm` SET `m_read` = '1' WHERE `id` = '" + UTIL_ToString( id ) + "';";
        				if( mysql_real_query( (MYSQL *)conn, Query2.c_str( ), Query2.size( ) ) != 0 )
				                *error = mysql_error( (MYSQL *)conn );
				}
                                //Delete messages from statspage directly
                                if( read == 2 ) {
                                        string Query3 = "DELETE FROM `oh_pm` WHERE `id` = '" + UTIL_ToString( id ) + "';";
                                        if( mysql_real_query( (MYSQL *)conn, Query3.c_str( ), Query3.size( ) ) != 0 )
                                                *error = mysql_error( (MYSQL *)conn );
                                }

                   	}
                        else
                                *error = "error checking message [" + name + "] - row doesn't have 4 columns";

                        mysql_free_result( Result );
                }
                else
                        *error = mysql_error( (MYSQL *)conn );
        }
        return InboxSummary;
}

uint32_t MySQLDotAGameAdd( void *conn, string *error, uint32_t botid, uint32_t gameid, uint32_t winner, uint32_t min, uint32_t sec )
{
	uint32_t RowID = 0;
	string Query = "INSERT INTO oh_dotagames ( botid, gameid, winner, min, sec ) VALUES ( " + UTIL_ToString( botid ) + ", " + UTIL_ToString( gameid ) + ", " + UTIL_ToString( winner ) + ", " + UTIL_ToString( min ) + ", " + UTIL_ToString( sec ) + " )";
	string GIQuery = "UPDATE `oh_game_info` SET `winner` = '" + UTIL_ToString( winner ) + "', `min` = '" + UTIL_ToString( min ) + "', `sec` = '" + UTIL_ToString( sec ) + "' WHERE `gameid` = '" + UTIL_ToString( gameid ) + "'; ";

        if( mysql_real_query( (MYSQL *)conn, GIQuery.c_str( ), GIQuery.size( ) ) != 0 )
                *error = mysql_error( (MYSQL *)conn );
        else
                RowID = mysql_insert_id( (MYSQL *)conn );

	if( mysql_real_query( (MYSQL *)conn, Query.c_str( ), Query.size( ) ) != 0 )
		*error = mysql_error( (MYSQL *)conn );
	else
		RowID = mysql_insert_id( (MYSQL *)conn );

	return RowID;
}

uint32_t MySQLDotAPlayerAdd( void *conn, string *error, uint32_t botid, uint32_t gameid, uint32_t colour, uint32_t kills, uint32_t deaths, uint32_t creepkills, uint32_t creepdenies, uint32_t assists, uint32_t gold, uint32_t neutralkills, string item1, string item2, string item3, string item4, string item5, string item6, string hero, uint32_t newcolour, uint32_t towerkills, uint32_t raxkills, uint32_t courierkills, uint32_t level )
{
	uint32_t RowID = 0;
	string EscItem1 = MySQLEscapeString( conn, item1 );
	string EscItem2 = MySQLEscapeString( conn, item2 );
	string EscItem3 = MySQLEscapeString( conn, item3 );
	string EscItem4 = MySQLEscapeString( conn, item4 );
	string EscItem5 = MySQLEscapeString( conn, item5 );
	string EscItem6 = MySQLEscapeString( conn, item6 );
	string EscHero = MySQLEscapeString( conn, hero );
	string Query = "INSERT INTO oh_dotaplayers ( botid, gameid, colour, kills, deaths, creepkills, creepdenies, assists, gold, neutralkills, item1, item2, item3, item4, item5, item6, hero, newcolour, towerkills, raxkills, courierkills, level ) VALUES ( " + UTIL_ToString( botid ) + ", " + UTIL_ToString( gameid ) + ", " + UTIL_ToString( colour ) + ", " + UTIL_ToString( kills ) + ", " + UTIL_ToString( deaths ) + ", " + UTIL_ToString( creepkills ) + ", " + UTIL_ToString( creepdenies ) + ", " + UTIL_ToString( assists ) + ", " + UTIL_ToString( gold ) + ", " + UTIL_ToString( neutralkills ) + ", '" + EscItem1 + "', '" + EscItem2 + "', '" + EscItem3 + "', '" + EscItem4 + "', '" + EscItem5 + "', '" + EscItem6 + "', '" + EscHero + "', " + UTIL_ToString( newcolour ) + ", " + UTIL_ToString( towerkills ) + ", " + UTIL_ToString( raxkills ) + ", " + UTIL_ToString( courierkills ) + ", " + UTIL_ToString( level ) + " )";

	if( mysql_real_query( (MYSQL *)conn, Query.c_str( ), Query.size( ) ) != 0 )
		*error = mysql_error( (MYSQL *)conn );
	else
		RowID = mysql_insert_id( (MYSQL *)conn );

	return RowID;
}

CDBDotAPlayerSummary *MySQLDotAPlayerSummaryCheck( void *conn, string *error, uint32_t botid, string name )
{
	transform( name.begin( ), name.end( ), name.begin( ), (int(*)(int))tolower );
	string EscName = MySQLEscapeString( conn, name );
	CDBDotAPlayerSummary *DotAPlayerSummary = NULL;
	string Query = "SELECT COUNT(oh_dotaplayers.id), SUM(kills), SUM(deaths), SUM(creepkills), SUM(creepdenies), SUM(assists), SUM(neutralkills), SUM(towerkills), SUM(raxkills), SUM(courierkills) FROM oh_gameplayers LEFT JOIN oh_games ON oh_games.id=oh_gameplayers.gameid LEFT JOIN oh_dotaplayers ON oh_dotaplayers.gameid=oh_games.id AND oh_dotaplayers.colour=oh_gameplayers.colour WHERE name LIKE '" + EscName + "'";

	if( mysql_real_query( (MYSQL *)conn, Query.c_str( ), Query.size( ) ) != 0 )
		*error = mysql_error( (MYSQL *)conn );
	else
	{
		MYSQL_RES *Result = mysql_store_result( (MYSQL *)conn );

		if( Result )
		{
			vector<string> Row = MySQLFetchRow( Result );

			if( Row.size( ) == 10 )
			{
				uint32_t TotalGames = UTIL_ToUInt32( Row[0] );

				if( TotalGames > 0 )
				{
					uint32_t TotalWins = 0;
					uint32_t TotalLosses = 0;
					uint32_t TotalKills = UTIL_ToUInt32( Row[1] );
					uint32_t TotalDeaths = UTIL_ToUInt32( Row[2] );
					uint32_t TotalCreepKills = UTIL_ToUInt32( Row[3] );
					uint32_t TotalCreepDenies = UTIL_ToUInt32( Row[4] );
					uint32_t TotalAssists = UTIL_ToUInt32( Row[5] );
					uint32_t TotalNeutralKills = UTIL_ToUInt32( Row[6] );
					uint32_t TotalTowerKills = UTIL_ToUInt32( Row[7] );
					uint32_t TotalRaxKills = UTIL_ToUInt32( Row[8] );
					uint32_t TotalCourierKills = UTIL_ToUInt32( Row[9] );

					// calculate total wins

					string Query2 = "SELECT COUNT(*) FROM oh_gameplayers LEFT JOIN oh_games ON oh_games.id=oh_gameplayers.gameid LEFT JOIN oh_dotaplayers ON oh_dotaplayers.gameid=oh_games.id AND oh_dotaplayers.colour=oh_gameplayers.colour LEFT JOIN oh_dotagames ON oh_games.id=oh_dotagames.gameid WHERE name='" + EscName + "' AND ((winner=1 AND dotaplayers.newcolour>=1 AND dotaplayers.newcolour<=5) OR (winner=2 AND dotaplayers.newcolour>=7 AND dotaplayers.newcolour<=11))";

					if( mysql_real_query( (MYSQL *)conn, Query2.c_str( ), Query2.size( ) ) != 0 )
						*error = mysql_error( (MYSQL *)conn );
					else
					{
						MYSQL_RES *Result2 = mysql_store_result( (MYSQL *)conn );

						if( Result2 )
						{
							vector<string> Row2 = MySQLFetchRow( Result2 );

							if( Row2.size( ) == 1 )
								TotalWins = UTIL_ToUInt32( Row2[0] );
							else
								*error = "error checking dotaplayersummary wins [" + name + "] - row doesn't have 1 column";

							mysql_free_result( Result2 );
						}
						else
							*error = mysql_error( (MYSQL *)conn );
					}

					// calculate total losses

					string Query3 = "SELECT COUNT(*) FROM oh_gameplayers LEFT JOIN oh_games ON oh_games.id=oh_gameplayers.gameid LEFT JOIN oh_dotaplayers ON oh_dotaplayers.gameid=oh_games.id AND oh_dotaplayers.colour=oh_gameplayers.colour LEFT JOIN oh_dotagames ON oh_games.id=oh_dotagames.gameid WHERE name='" + EscName + "' AND ((winner=2 AND dotaplayers.newcolour>=1 AND dotaplayers.newcolour<=5) OR (winner=1 AND dotaplayers.newcolour>=7 AND dotaplayers.newcolour<=11))";

					if( mysql_real_query( (MYSQL *)conn, Query3.c_str( ), Query3.size( ) ) != 0 )
						*error = mysql_error( (MYSQL *)conn );
					else
					{
						MYSQL_RES *Result3 = mysql_store_result( (MYSQL *)conn );

						if( Result3 )
						{
							vector<string> Row3 = MySQLFetchRow( Result3 );

							if( Row3.size( ) == 1 )
								TotalLosses = UTIL_ToUInt32( Row3[0] );
							else
								*error = "error checking dotaplayersummary losses [" + name + "] - row doesn't have 1 column";

							mysql_free_result( Result3 );
						}
						else
							*error = mysql_error( (MYSQL *)conn );
					}

					// done

					DotAPlayerSummary = new CDBDotAPlayerSummary( string( ), name, TotalGames, TotalWins, TotalLosses, TotalKills, TotalDeaths, TotalCreepKills, TotalCreepDenies, TotalAssists, TotalNeutralKills, TotalTowerKills, TotalRaxKills, TotalCourierKills );
				}
			}
			else
				*error = "error checking dotaplayersummary [" + name + "] - row doesn't have 10 columns";

			mysql_free_result( Result );
		}
		else
			*error = mysql_error( (MYSQL *)conn );
	}

	return DotAPlayerSummary;
}

bool MySQLDownloadAdd( void *conn, string *error, uint32_t botid, string map, uint32_t mapsize, string name, string ip, uint32_t spoofed, string spoofedrealm, uint32_t downloadtime )
{
	bool Success = false;
	string EscMap = MySQLEscapeString( conn, map );
	string EscName = MySQLEscapeString( conn, name );
	string EscIP = MySQLEscapeString( conn, ip );
	string EscSpoofedRealm = MySQLEscapeString( conn, spoofedrealm );
	string Query = "INSERT INTO oh_downloads ( botid, map, mapsize, datetime, name, ip, spoofed, spoofedrealm, downloadtime ) VALUES ( " + UTIL_ToString( botid ) + ", '" + EscMap + "', " + UTIL_ToString( mapsize ) + ", NOW( ), '" + EscName + "', '" + EscIP + "', " + UTIL_ToString( spoofed ) + ", '" + EscSpoofedRealm + "', " + UTIL_ToString( downloadtime ) + " )";

	if( mysql_real_query( (MYSQL *)conn, Query.c_str( ), Query.size( ) ) != 0 )
		*error = mysql_error( (MYSQL *)conn );
	else
		Success = true;

	return Success;
}

double MySQLScoreCheck( void *conn, string *error, uint32_t botid, string category, string name, string server )
{
	transform( name.begin( ), name.end( ), name.begin( ), (int(*)(int))tolower );
	string EscCategory = MySQLEscapeString( conn, category );
	string EscName = MySQLEscapeString( conn, name );
	string EscServer = MySQLEscapeString( conn, server );
	double Score = -100000.0;
	string Query = "SELECT score FROM oh_stats WHERE player_lower='" + EscName + "' AND realm='" + EscServer + "'";

	if( mysql_real_query( (MYSQL *)conn, Query.c_str( ), Query.size( ) ) != 0 )
		*error = mysql_error( (MYSQL *)conn );
	else
	{
		MYSQL_RES *Result = mysql_store_result( (MYSQL *)conn );

		if( Result )
		{
			vector<string> Row = MySQLFetchRow( Result );

			if( Row.size( ) == 1 )
				Score = UTIL_ToDouble( Row[0] );
			/* else
				*error = "error checking score [" + category + " : " + name + " : " + server + "] - row doesn't have 1 column"; */

			mysql_free_result( Result );
		}
		else
			*error = mysql_error( (MYSQL *)conn );
	}

	return Score;
}

uint32_t MySQLW3MMDPlayerAdd( void *conn, string *error, uint32_t botid, string category, uint32_t gameid, uint32_t pid, string name, string flag, uint32_t leaver, uint32_t practicing )
{
	transform( name.begin( ), name.end( ), name.begin( ), (int(*)(int))tolower );
	uint32_t RowID = 0;
	string EscCategory = MySQLEscapeString( conn, category );
	string EscName = MySQLEscapeString( conn, name );
	string EscFlag = MySQLEscapeString( conn, flag );
	string Query = "INSERT INTO oh_w3mmdplayers ( botid, category, gameid, pid, name, flag, leaver, practicing ) VALUES ( " + UTIL_ToString( botid ) + ", '" + EscCategory + "', " + UTIL_ToString( gameid ) + ", " + UTIL_ToString( pid ) + ", '" + EscName + "', '" + EscFlag + "', " + UTIL_ToString( leaver ) + ", " + UTIL_ToString( practicing ) + " )";

	if( mysql_real_query( (MYSQL *)conn, Query.c_str( ), Query.size( ) ) != 0 )
		*error = mysql_error( (MYSQL *)conn );
	else
		RowID = mysql_insert_id( (MYSQL *)conn );

	return RowID;
}

bool MySQLW3MMDVarAdd( void *conn, string *error, uint32_t botid, uint32_t gameid, map<VarP,int32_t> var_ints )
{
	if( var_ints.empty( ) )
		return false;

	bool Success = false;
	string Query;

        for( map<VarP,int32_t> :: iterator i = var_ints.begin( ); i != var_ints.end( ); ++i )
	{
		string EscVarName = MySQLEscapeString( conn, i->first.second );

		if( Query.empty( ) )
			Query = "INSERT INTO oh_w3mmdvars ( botid, gameid, pid, varname, value_int ) VALUES ( " + UTIL_ToString( botid ) + ", " + UTIL_ToString( gameid ) + ", " + UTIL_ToString( i->first.first ) + ", '" + EscVarName + "', " + UTIL_ToString( i->second ) + " )";
		else
			Query += ", ( " + UTIL_ToString( botid ) + ", " + UTIL_ToString( gameid ) + ", " + UTIL_ToString( i->first.first ) + ", '" + EscVarName + "', " + UTIL_ToString( i->second ) + " )";
	}

	if( mysql_real_query( (MYSQL *)conn, Query.c_str( ), Query.size( ) ) != 0 )
		*error = mysql_error( (MYSQL *)conn );
	else
		Success = true;

	return Success;
}

bool MySQLW3MMDVarAdd( void *conn, string *error, uint32_t botid, uint32_t gameid, map<VarP,double> var_reals )
{
	if( var_reals.empty( ) )
		return false;

	bool Success = false;
	string Query;

        for( map<VarP,double> :: iterator i = var_reals.begin( ); i != var_reals.end( ); ++i )
	{
		string EscVarName = MySQLEscapeString( conn, i->first.second );

		if( Query.empty( ) )
			Query = "INSERT INTO oh_w3mmdvars ( botid, gameid, pid, varname, value_real ) VALUES ( " + UTIL_ToString( botid ) + ", " + UTIL_ToString( gameid ) + ", " + UTIL_ToString( i->first.first ) + ", '" + EscVarName + "', " + UTIL_ToString( i->second, 10 ) + " )";
		else
			Query += ", ( " + UTIL_ToString( botid ) + ", " + UTIL_ToString( gameid ) + ", " + UTIL_ToString( i->first.first ) + ", '" + EscVarName + "', " + UTIL_ToString( i->second, 10 ) + " )";
	}

	if( mysql_real_query( (MYSQL *)conn, Query.c_str( ), Query.size( ) ) != 0 )
		*error = mysql_error( (MYSQL *)conn );
	else
		Success = true;

	return Success;
}

bool MySQLW3MMDVarAdd( void *conn, string *error, uint32_t botid, uint32_t gameid, map<VarP,string> var_strings )
{
	if( var_strings.empty( ) )
		return false;

	bool Success = false;
	string Query;

        for( map<VarP,string> :: iterator i = var_strings.begin( ); i != var_strings.end( ); ++i )
	{
		string EscVarName = MySQLEscapeString( conn, i->first.second );
		string EscValueString = MySQLEscapeString( conn, i->second );

		if( Query.empty( ) )
			Query = "INSERT INTO oh_w3mmdvars ( botid, gameid, pid, varname, value_string ) VALUES ( " + UTIL_ToString( botid ) + ", " + UTIL_ToString( gameid ) + ", " + UTIL_ToString( i->first.first ) + ", '" + EscVarName + "', '" + EscValueString + "' )";
		else
			Query += ", ( " + UTIL_ToString( botid ) + ", " + UTIL_ToString( gameid ) + ", " + UTIL_ToString( i->first.first ) + ", '" + EscVarName + "', '" + EscValueString + "' )";
	}

	if( mysql_real_query( (MYSQL *)conn, Query.c_str( ), Query.size( ) ) != 0 )
		*error = mysql_error( (MYSQL *)conn );
	else
		Success = true;

	return Success;
}

//
// MySQL Callables
//

void CMySQLCallable :: Init( )
{
	CBaseCallable :: Init( );

#ifndef WIN32
	// disable SIGPIPE since this is (or should be) a new thread and it doesn't inherit the spawning thread's signal handlers
	// MySQL should automatically disable SIGPIPE when we initialize it but we do so anyway here

	signal( SIGPIPE, SIG_IGN );
#endif

	mysql_thread_init( );

	if( !m_Connection )
	{
		if( !( m_Connection = mysql_init( NULL ) ) )
			m_Error = mysql_error( (MYSQL *)m_Connection );

		my_bool Reconnect = true;
		mysql_options( (MYSQL *)m_Connection, MYSQL_OPT_RECONNECT, &Reconnect );

		if( !( mysql_real_connect( (MYSQL *)m_Connection, m_SQLServer.c_str( ), m_SQLUser.c_str( ), m_SQLPassword.c_str( ), m_SQLDatabase.c_str( ), m_SQLPort, NULL, 0 ) ) )
			m_Error = mysql_error( (MYSQL *)m_Connection );
	}
	else if( mysql_ping( (MYSQL *)m_Connection ) != 0 )
		m_Error = mysql_error( (MYSQL *)m_Connection );
}

void CMySQLCallable :: Close( )
{
	mysql_thread_end( );

	CBaseCallable :: Close( );
}

void CMySQLCallableFromCheck :: operator( )( )
{
        Init( );

        if( m_Error.empty( ) )
                m_Result = MySQLFromCheck( m_Connection, &m_Error, m_SQLBotID, m_IP );

        Close( );
}

void CMySQLCallableRegAdd :: operator( )( )
{
        Init( );

        if( m_Error.empty( ) )
                m_Result = MySQLRegAdd( m_Connection, &m_Error, m_SQLBotID, m_User, m_Server, m_Mail, m_Password, m_Type );

        Close( );
}

void CMySQLCallableStatsSystem :: operator( )( )
{
        Init( );

        if( m_Error.empty( ) )
                m_Result = MySQLStatsSystem( m_Connection, &m_Error, m_SQLBotID, m_User, m_Input, m_One, m_Type );

        Close( );
}

void CMySQLCallablePWCheck :: operator( )( )
{
        Init( );

        if( m_Error.empty( ) )
                m_Result = MySQLPWCheck( m_Connection, &m_Error, m_SQLBotID, m_User );

        Close( );
}

void CMySQLCallablePassCheck :: operator( )( )
{
        Init( );

        if( m_Error.empty( ) )
                m_Result = MySQLPassCheck( m_Connection, &m_Error, m_SQLBotID, m_User, m_Pass, m_ST );

        Close( );
}

void CMySQLCallablepm :: operator( )( )
{
        Init( );

        if( m_Error.empty( ) )
                m_Result = MySQLpm( m_Connection, &m_Error, m_SQLBotID, m_User, m_Listener, m_Status, m_Message, m_Type );

        Close( );
}

void CMySQLCallablePList :: operator( )( )
{
        Init( );

        if( m_Error.empty( ) )
                m_Result = MySQLPList( m_Connection, &m_Error, m_SQLBotID, m_Server );

        Close( );
}

void CMySQLCallableFlameList :: operator( )( )
{
        Init( );

        if( m_Error.empty( ) )
                m_Result = MySQLFlameList( m_Connection, &m_Error, m_SQLBotID );

        Close( );
}

void CMySQLCallableAnnounceList :: operator( )( )
{
        Init( );

        if( m_Error.empty( ) )
                m_Result = MySQLAnnounceList( m_Connection, &m_Error, m_SQLBotID );

        Close( );
}

void CMySQLCallableDCountryList :: operator( )( )
{
        Init( );

        if( m_Error.empty( ) )
                m_Result = MySQLDCountryList( m_Connection, &m_Error, m_SQLBotID );

        Close( );
}

void CMySQLCallableStoreLog :: operator( )( )
{
        Init( );

        if( m_Error.empty( ) )
                MySQLStoreLog( m_Connection, &m_Error, m_SQLBotID, m_ChatID, m_Game, m_Admin );

        Close( );
}

void CMySQLCallablegs :: operator( )( )
{
        Init( );

        if( m_Error.empty( ) )
                MySQLgs( m_Connection, &m_Error, m_SQLBotID, m_ChatID, m_GN, m_ST, m_GameType );

        Close( );
}

void CMySQLCallablepenp :: operator( )( )
{
        Init( );

        if( m_Error.empty( ) )
                m_Result = MySQLpenp( m_Connection, &m_Error, m_SQLBotID, m_Name, m_Reason, m_Admin, m_Amount, m_Type );

        Close( );
}

void CMySQLCallableBanCount :: operator( )( )
{
	Init( );

	if( m_Error.empty( ) )
		m_Result = MySQLBanCount( m_Connection, &m_Error, m_SQLBotID, m_Server );

	Close( );
}

void CMySQLCallableBanCheck :: operator( )( )
{
	Init( );

	if( m_Error.empty( ) )
		m_Result = MySQLBanCheck( m_Connection, &m_Error, m_SQLBotID, m_Server, m_User, m_IP );

	Close( );
}

void CMySQLCallableBanCheck2 :: operator( )( )
{
        Init( );

        if( m_Error.empty( ) )
                m_Result = MySQLBanCheck2( m_Connection, &m_Error, m_SQLBotID, m_Server, m_User, m_Type );

        Close( );
}

void CMySQLCallableBanAdd :: operator( )( )
{
	Init( );

	if( m_Error.empty( ) )
		m_Result = MySQLBanAdd( m_Connection, &m_Error, m_SQLBotID, m_Server, m_User, m_IP, m_GameName, m_Admin, m_Reason, m_BanTime, m_Country );

	Close( );
}

void CMySQLCallablePUp :: operator( )( )
{
        Init( );

        if( m_Error.empty( ) )
                m_Result = MySQLPUp( m_Connection, &m_Error, m_SQLBotID, m_Name, m_Level, m_Realm, m_User );

        Close( );
}

void CMySQLCallableBanRemove :: operator( )( )
{
	Init( );

	if( m_Error.empty( ) )
	{
		if( m_Server.empty( ) )
			m_Result = MySQLBanRemove( m_Connection, &m_Error, m_SQLBotID, m_User );
		else
			m_Result = MySQLBanRemove( m_Connection, &m_Error, m_SQLBotID, m_Server, m_User );
	}

	Close( );
}

void CMySQLCallableBanList :: operator( )( )
{
	Init( );

	if( m_Error.empty( ) )
		m_Result = MySQLBanList( m_Connection, &m_Error, m_SQLBotID, m_Server );

	Close( );
}

void CMySQLCallableTBRemove :: operator( )( )
{
        Init( );

        if( m_Error.empty( ) )
                m_Result = MySQLTBRemove( m_Connection, &m_Error, m_SQLBotID, m_Server );

        Close( );
}

void CMySQLCallableCommandList :: operator( )( )
{
	Init( );

	if( m_Error.empty( ) )
		m_Result = MySQLCommandList( m_Connection, &m_Error, m_SQLBotID );

	Close( );
}

void CMySQLCallableClean :: operator( )( )
{
        Init( );

        if( m_Error.empty( ) )
                m_Result = MySQLClean( m_Connection, &m_Error, m_SQLBotID );

        Close( );
}

void CMySQLCallableGameAdd :: operator( )( )
{
	Init( );

	if( m_Error.empty( ) )
		m_Result = MySQLGameAdd( m_Connection, &m_Error, m_SQLBotID, m_Server, m_Map, m_GameName, m_OwnerName, m_Duration, m_GameState, m_CreatorName, m_CreatorServer, m_GameType, m_LobbyLog, m_GameLog );

	Close( );
}

void CMySQLCallableGameUpdate :: operator( )( )
{
	Init( );

	if( m_Error.empty( ) )
		m_Result = MySQLGameUpdate( m_Connection, &m_Error, m_SQLBotID, m_Map, m_GameName, m_OwnerName, m_CreatorName, m_Players, m_Usernames, m_SlotsTotal, m_TotalGames, m_TotalPlayers, m_Add );

	Close( );
}

void CMySQLCallableGamePlayerAdd :: operator( )( )
{
	Init( );

	if( m_Error.empty( ) )
		m_Result = MySQLGamePlayerAdd( m_Connection, &m_Error, m_SQLBotID, m_GameID, m_Name, m_IP, m_Spoofed, m_SpoofedRealm, m_Reserved, m_LoadingTime, m_Left, m_LeftReason, m_Team, m_Colour );

	Close( );
}

void CMySQLCallableGamePlayerSummaryCheck :: operator( )( )
{
	Init( );

	if( m_Error.empty( ) )
		m_Result = MySQLGamePlayerSummaryCheck( m_Connection, &m_Error, m_SQLBotID, m_Name );

	Close( );
}

void CMySQLCallableStatsPlayerSummaryCheck :: operator( )( )
{
        Init( );

        if( m_Error.empty( ) )
                m_Result = MySQLStatsPlayerSummaryCheck( m_Connection, &m_Error, m_SQLBotID, m_Name );

        Close( );
}

void CMySQLCallableInboxSummaryCheck :: operator( )( )
{
        Init( );

        if( m_Error.empty( ) )
                m_Result = MySQLInboxSummaryCheck( m_Connection, &m_Error, m_SQLBotID, m_Name );

        Close( );
}

void CMySQLCallableDotAGameAdd :: operator( )( )
{
	Init( );

	if( m_Error.empty( ) )
		m_Result = MySQLDotAGameAdd( m_Connection, &m_Error, m_SQLBotID, m_GameID, m_Winner, m_Min, m_Sec );

	Close( );
}

void CMySQLCallableDotAPlayerAdd :: operator( )( )
{
	Init( );

	if( m_Error.empty( ) )
		m_Result = MySQLDotAPlayerAdd( m_Connection, &m_Error, m_SQLBotID, m_GameID, m_Colour, m_Kills, m_Deaths, m_CreepKills, m_CreepDenies, m_Assists, m_Gold, m_NeutralKills, m_Item1, m_Item2, m_Item3, m_Item4, m_Item5, m_Item6, m_Hero, m_NewColour, m_TowerKills, m_RaxKills, m_CourierKills, m_Level );

	Close( );
}

void CMySQLCallableDotAPlayerSummaryCheck :: operator( )( )
{
	Init( );

	if( m_Error.empty( ) )
		m_Result = MySQLDotAPlayerSummaryCheck( m_Connection, &m_Error, m_SQLBotID, m_Name );

	Close( );
}

void CMySQLCallableDownloadAdd :: operator( )( )
{
	Init( );

	if( m_Error.empty( ) )
		m_Result = MySQLDownloadAdd( m_Connection, &m_Error, m_SQLBotID, m_Map, m_MapSize, m_Name, m_IP, m_Spoofed, m_SpoofedRealm, m_DownloadTime );

	Close( );
}

void CMySQLCallableScoreCheck :: operator( )( )
{
	Init( );

	if( m_Error.empty( ) )
		m_Result = MySQLScoreCheck( m_Connection, &m_Error, m_SQLBotID, m_Category, m_Name, m_Server );

	Close( );
}

void CMySQLCallableW3MMDPlayerAdd :: operator( )( )
{
	Init( );

	if( m_Error.empty( ) )
		m_Result = MySQLW3MMDPlayerAdd( m_Connection, &m_Error, m_SQLBotID, m_Category, m_GameID, m_PID, m_Name, m_Flag, m_Leaver, m_Practicing );

	Close( );
}

void CMySQLCallableW3MMDVarAdd :: operator( )( )
{
	Init( );

	if( m_Error.empty( ) )
	{
		if( m_ValueType == VALUETYPE_INT )
			m_Result = MySQLW3MMDVarAdd( m_Connection, &m_Error, m_SQLBotID, m_GameID, m_VarInts );
		else if( m_ValueType == VALUETYPE_REAL )
			m_Result = MySQLW3MMDVarAdd( m_Connection, &m_Error, m_SQLBotID, m_GameID, m_VarReals );
		else
			m_Result = MySQLW3MMDVarAdd( m_Connection, &m_Error, m_SQLBotID, m_GameID, m_VarStrings );
	}

	Close( );
}

#endif
