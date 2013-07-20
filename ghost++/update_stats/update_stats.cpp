/*

   Copyright [2013] [OHsystem]

       OHSystem-Bot is free software: you can redistribute it and/or modify
       it under the terms of the GNU General Public License as published by
       the Free Software Foundation, either version 3 of the License, or
       (at your option) any later version.

   This is modified from GHOST++: http://ghostplusplus.googlecode.com/

*/

#include <iomanip>
#include <iostream>
#include <map>
#include <queue>
#include <sstream>
#include <string>
#include <vector>
#include <algorithm>

using namespace std;

#ifdef WIN32
 #include "ms_stdint.h"
#else
 #include <stdint.h>
#endif

#include "config.h"

#include <string.h>

#ifdef WIN32
 #include <winsock.h>
#endif

#include <mysql/mysql.h>

#include <stdio.h>

void CONSOLE_Print( string message )
{
	cout << message << endl;
}

string MySQLEscapeString( MYSQL *conn, string str )
{
	char *to = new char[str.size( ) * 2 + 1];
	unsigned long size = mysql_real_escape_string( conn, to, str.c_str( ), str.size( ) );
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

		for( unsigned int i = 0; i < mysql_num_fields( res ); i++ )
		{
			if( Row[i] )
				Result.push_back( string( Row[i], Lengths[i] ) );
			else
				Result.push_back( string( ) );
		}
	}

	return Result;
}

string UTIL_ToString( uint32_t i )
{
	string result;
	stringstream SS;
	SS << i;
	SS >> result;
	return result;
}

string Int32_ToString( int32_t i )
{
	string result;
        stringstream SS;
        SS << i;
        SS >> result;
        return result;
}
string UTIL_ToString( float f, int digits )
{
	string result;
	stringstream SS;
	SS << std :: fixed << std :: setprecision( digits ) << f;
	SS >> result;
	return result;
}

uint32_t UTIL_ToUInt32( string &s )
{
	uint32_t result;
	stringstream SS;
	SS << s;
	SS >> result;
	return result;
}

int32_t UTIL_ToInt32( string &s )
{
        int32_t result;
        stringstream SS;
        SS << s;
        SS >> result;
        return result;
}

float UTIL_ToFloat( string &s )
{
	float result;
	stringstream SS;
	SS << s;
	SS >> result;
	return result;
}

int main( int argc, char **argv )
{
	string CFGFile = "default.cfg";

	if( argc > 1 && argv[1] )
		CFGFile = argv[1];

	CConfig CFG;
	CFG.Read( CFGFile );
	string Server = CFG.GetString( "db_mysql_server", string( ) );
	string Database = CFG.GetString( "db_mysql_database", "ghost" );
	string User = CFG.GetString( "db_mysql_user", string( ) );
	string Password = CFG.GetString( "db_mysql_password", string( ) );
	int Port = CFG.GetInt( "db_mysql_port", 0 );

	cout << "connecting to database server" << endl;
	MYSQL *Connection = NULL;

	if( !( Connection = mysql_init( NULL ) ) )
	{
		cout << "error: " << mysql_error( Connection ) << endl;
		return 1;
	}

	my_bool Reconnect = true;
	mysql_options( Connection, MYSQL_OPT_RECONNECT, &Reconnect );

	if( !( mysql_real_connect( Connection, Server.c_str( ), User.c_str( ), Password.c_str( ), Database.c_str( ), Port, NULL, 0 ) ) )
	{
		cout << "error: " << mysql_error( Connection ) << endl;
		return 1;
	}

	cout << "connected" << endl;
	cout << "beginning transaction" << endl;

	string QBegin = "BEGIN";

	if( mysql_real_query( Connection, QBegin.c_str( ), QBegin.size( ) ) != 0 )
	{
		cout << "error: " << mysql_error( Connection ) << endl;
		return 1;
	}

	cout << "getting unscored games" << endl;
	queue<uint32_t> UnscoredGames;

	string QSelectUnscored = "SELECT `gameid` FROM `games` WHERE `stats` = '0' ORDER BY id;";

	if( mysql_real_query( Connection, QSelectUnscored.c_str( ), QSelectUnscored.size( ) ) != 0 )
	{
		cout << "error: " << mysql_error( Connection ) << endl;
		return 1;
	}
	else
	{
		MYSQL_RES *Result = mysql_store_result( Connection );

		if( Result )
		{
			vector<string> Row = MySQLFetchRow( Result );

			while( !Row.empty( ) )
			{
				UnscoredGames.push( UTIL_ToUInt32( Row[0] ) );
				Row = MySQLFetchRow( Result );
			}

			mysql_free_result( Result );
		}
		else
		{
			cout << "error: " << mysql_error( Connection ) << endl;
			return 1;
		}
	}

	cout << "found " << UnscoredGames.size( ) << " unscored games" << endl;

	while( !UnscoredGames.empty( ) )
	{
		uint32_t GameID = UnscoredGames.front( );
		UnscoredGames.pop( );

		string QSelectPlayers = "SELECT s.id, gp.name, dp.kills, dp.deaths, dp.assists, dp.creepkills, dp.creepdenies, dp.neutralkills, dp.towerkills, dp.raxkills, gp.spoofedrealm,gp.reserved, gp.left, gp.ip, g.duration, dg.winner, dp.newcolour, gp.team, s.streak, s.maxstreak, s.losingstreak, s.maxlosingstreak FROM gameplayers as gp LEFT JOIN dotaplayers as dp ON gp.gameid=dp.gameid AND gp.colour=dp.newcolour LEFT JOIN games as g on g.id=gp.gameid LEFT JOIN stats as s ON gp.name = s.player_lower LEFT JOIN dotagames as dg ON dg.gameid=gp.gameid WHERE gp.gameid = " + UTIL_ToString( GameID );
		if( mysql_real_query( Connection, QSelectPlayers.c_str( ), QSelectPlayers.size( ) ) != 0 )
		{
			cout << "error: " << mysql_error( Connection ) << endl;
			return 1;
		}
		else
		{
			MYSQL_RES *Result = mysql_store_result( Connection );

			if( Result )
			{
				cout << "gameid " << UTIL_ToString( GameID ) << " found" << endl;

				bool ignore = false;
				uint32_t rowids[10];
				string names[10];
				string servers[10];
				bool exists[10];
				int num_players = 0;
				string score[10];
				int64_t nscore[10];
				int player_teams[10];
				int num_teams = 2;
				float team_ratings[2];
				float team_winners[2];
				int team_numplayers[2];
				team_ratings[0] = 0.0;
				team_ratings[1] = 0.0;
				team_numplayers[0] = 0;
				team_numplayers[1] = 0;
                                string lnames[10];
                                string ips[10];
                                uint32_t reserved[10];
                                uint32_t lt[10];
                                uint32_t left[10];
                                uint32_t team[10];
                                uint32_t colour[10];
                                uint32_t k[10];
                                uint32_t d[10];
                                uint32_t a[10];
                                uint32_t c[10];
                                uint32_t de[10];
                                uint32_t n[10];
                                uint32_t t[10];
                                uint32_t r[10];
				uint32_t ScoreStart = 0;
				int32_t ScoreWin = 5;
				int32_t ScoreLosse = 3;
				string streak[10];
				uint32_t maxstreak[10];
				string lstreak[10];
				uint32_t maxlstreak[10];
				uint32_t zd[10];
				uint32_t bp[1];
				int win[10];
				int losses[10];
				int draw[10];
				int nstreak[10];
                                int nlstreak[10];
				uint32_t banned[10];
				uint32_t leaver[10];

				vector<string> Row = MySQLFetchRow( Result );

				while( Row.size( ) == 22 )
				{
					if( num_players >= 10 )
					{
						cout << "gameid " << UTIL_ToString( GameID ) << " has more than 10 players, ignoring" << endl;
						ignore = true;
						break;
					}

					uint32_t Winner = UTIL_ToUInt32( Row[15] );

					if( Winner != 1 && Winner != 2 && Winner != 0)
					{
						cout << "gameid " << UTIL_ToString( GameID ) << " is not a two team map, ignoring" << endl;
						ignore = true;
						break;
					}
					else if( Winner == 1 )
					{
						team_winners[0] = 1.0;
						team_winners[1] = 0.0;
					}
					else
					{
						team_winners[0] = 0.0;
						team_winners[1] = 1.0;
					}

					if( !Row[55].empty( ) )
						rowids[num_players] = UTIL_ToUInt32( Row[55] );
					else
						rowids[num_players] = 0;

					names[num_players] = Row[1];
                                        string name = Row[1];
                                        std::transform( name.begin(), name.end(), name.begin(), ::tolower);
                                        lnames[num_players] = name;
					servers[num_players] = Row[10];
                                        score[num_players] = "";
                                        nscore[num_players] = 0;
					banned[num_players] = 0;

                                        string PlayerStatus = "SELECT name FROM `bans` WHERE name = '" + Row[1] + "';";
                                        if( mysql_real_query( Connection, PlayerStatus.c_str( ), PlayerStatus.size( ) ) != 0 )
                                        {
                                                cout << "error: " << mysql_error( Connection ) << endl;
                                                return 1;
                                        }
                                        else
                                        {
                                                MYSQL_RES *Result = mysql_store_result( Connection );
                                                if( Result )
                                                {
                                                        vector<string> Row = MySQLFetchRow( Result );
                                                        if( Row.size( ) == 1 )
                                                                banned[num_players] = 1;
                                                }
                                        }

					if( !Row[0].empty( ) )
						exists[num_players] = true;
					else
					{
						cout << "new player [" << Row[1] << "] found" << endl;
						exists[num_players] = false;
					}

					uint32_t Colour = UTIL_ToUInt32( Row[16] );

					if( Colour >= 1 && Colour <= 5 )
					{
						team_numplayers[0]++;
						if( Winner == 1 )
						{
							score[num_players] = "score = score+" + UTIL_ToString( ScoreWin ) + ",";
							nscore[num_players] = ScoreWin;
							win[num_players] = 1;
							streak[num_players] = "streak = streak+1, ";
							lstreak[num_players] = "losingstreak = 0, ";
							nstreak[num_players] = 1;
							nlstreak[num_players] = 0;
							losses[num_players] = 0;
							draw[num_players] = 0;

							if( !Row[19].empty() && !Row[18].empty() && !Row[21].empty() )
							{
								if( UTIL_ToUInt32( Row[19] ) < UTIL_ToUInt32( Row[18] )+1 )
									maxstreak[num_players] = UTIL_ToUInt32( Row[19] )+1;
								else
									maxstreak[num_players] = UTIL_ToUInt32( Row[19] );

								maxlstreak[num_players] = UTIL_ToUInt32( Row[21] );
							}
							else
							{
								maxstreak[num_players] = 1;
								maxlstreak[num_players] = 0;
							}
						}
						else if( Winner == 2 )
						{
                                                        score[num_players] = "score = score-" + UTIL_ToString( ScoreLosse ) + ",";
                                                        nscore[num_players] = -ScoreLosse;
                                                        losses[num_players] = 1;
                                                        lstreak[num_players] = "losingstreak = losingstreak+1, ";
                                                        streak[num_players] = "streak = 0, ";
							nstreak[num_players] = 0;
							nlstreak[num_players] = 1;
                                                        win[num_players] = 0;
                                                        draw[num_players] = 0;

                                                        if( !Row[21].empty() && !Row[20].empty() && !Row[19].empty() )
							{
                        	                                if( UTIL_ToUInt32( Row[21] ) < UTIL_ToUInt32( Row[20] )+1 )
                	                                                maxlstreak[num_players] = UTIL_ToUInt32( Row[21] )+1;
        	                                                else
	                                                                maxlstreak[num_players] = UTIL_ToUInt32( Row[21] );

                                                                maxstreak[num_players] = UTIL_ToUInt32( Row[19] );
                                                        }
                                                        else
							{
                                                                maxlstreak[num_players] = 1;
								maxstreak[num_players] = 0;
							}
                                                }
						else
						{
							draw[num_players] = 1;
                                                        losses[num_players] = 0;
                                                        win[num_players] = 0;
							nstreak[num_players] = 0;
							nlstreak[num_players] = 0;
                                                        streak[num_players] = "";
                                                        lstreak[num_players] = "";
                                                        if( !Row[18].empty() && !Row[21].empty() )
                                                        {
                                                                maxstreak[num_players] = UTIL_ToUInt32( Row[19] );
                                                                maxlstreak[num_players] = UTIL_ToUInt32( Row[21] );
                                                        }
                                                        else
                                                        {
                                                                maxstreak[num_players] = 0;
                                                                maxlstreak[num_players] = 0;
                                                        }
						}
					}
					else if( Colour >= 7 && Colour <= 11 )
					{
						team_numplayers[1]++;
                                                if( Winner == 2 )
						{
                                                        score[num_players] = "score = score+" + UTIL_ToString( ScoreWin ) + ",";
                                                        nscore[num_players] = ScoreWin;
                                                        win[num_players] = 1;
                                                        streak[num_players] = "streak = streak+1, ";
                                                        lstreak[num_players] = "losingstreak = 0, ";
                                                        losses[num_players] = 0;
                                                        draw[num_players] = 0;
							nstreak[num_players] = 1;
							nlstreak[num_players] = 0;

							if( !Row[19].empty() && !Row[18].empty() && !Row[21].empty() )
							{
	                                                        if( UTIL_ToUInt32( Row[19] ) < UTIL_ToUInt32( Row[18] )+1 )
        	                                                        maxstreak[num_players] = UTIL_ToUInt32( Row[19] )+1;
                	                                        else
                        	                                        maxstreak[num_players] = UTIL_ToUInt32( Row[19] );

                                                                maxlstreak[num_players] = UTIL_ToUInt32( Row[21] );
                                                        }
                                                        else
                                                        {
                                                                maxstreak[num_players] = 1;
                                                                maxlstreak[num_players] = 0;
                                                        }
                                                }
						else if( Winner == 1 )
	                                        {
                                                        score[num_players] = "score = score-" + UTIL_ToString( ScoreLosse ) + ",";
                                                        nscore[num_players] = -ScoreLosse;
                                                        losses[num_players] = 1;
                                                        lstreak[num_players] = "losingstreak = losingstreak+1, ";
                                                        streak[num_players] = "streak = 0, ";
                                                        win[num_players] = 0;
                                                        draw[num_players] = 0;
							nstreak[num_players] = 0;
							nlstreak[num_players] = 1;

                                                        if( !Row[21].empty() && !Row[20].empty() && !Row[19].empty() )
							{
                        	                                if( UTIL_ToUInt32( Row[21] ) < UTIL_ToUInt32( Row[21] )+1 )
                	                                                maxlstreak[num_players] = UTIL_ToUInt32( Row[21] )+1;
        	                                                else
	                                                                maxlstreak[num_players] = UTIL_ToUInt32( Row[21] );

                                                                maxstreak[num_players] = UTIL_ToUInt32( Row[19] );
                                                        }
                                                        else
                                                        {
                                                                maxstreak[num_players] = 0;
                                                                maxlstreak[num_players] = 1;
                                                        }
                                                }
                                                else
                                                {
                                                        draw[num_players] = 1;
                                                        losses[num_players] = 0;
                                                        win[num_players] = 0;
							nstreak[num_players] = 0;
							nlstreak[num_players] = 0;
                                                        streak[num_players] = "";
                                                        lstreak[num_players] = "";
							if( !Row[19].empty() && !Row[21].empty() )
							{
								maxstreak[num_players] = UTIL_ToUInt32( Row[19] );
        	                                                maxlstreak[num_players] = UTIL_ToUInt32( Row[21] );
							}
                		                        else
		                                        {
								maxstreak[num_players] = 0;
								maxlstreak[num_players] = 0;
							}
                                                }
					}
					else
					{
						cout << "gameid " << UTIL_ToString( GameID ) << " has a player with an invalid newcolour, ignoring" << endl;
						ignore = true;
						break;
					}

					ips[num_players] = Row[13];
                                        reserved[num_players] = UTIL_ToUInt32( Row[11] );
                                        left[num_players] = UTIL_ToUInt32( Row[12] );
					team[num_players] = UTIL_ToUInt32( Row[17] );
					colour[num_players] = UTIL_ToUInt32( Row[16] );
					k[num_players] = UTIL_ToUInt32( Row[2] );
                                        d[num_players] = UTIL_ToUInt32( Row[3] );
					if( UTIL_ToUInt32( Row[3] ) == 0 )
						zd[num_players] = 1;
					else
						zd[num_players] = 0;

                                        a[num_players] = UTIL_ToUInt32( Row[4] );
                                        c[num_players] = UTIL_ToUInt32( Row[5] );
                                        de[num_players] = UTIL_ToUInt32( Row[6] );
                                        n[num_players] = UTIL_ToUInt32( Row[7] );
                                        t[num_players] = UTIL_ToUInt32( Row[8] );
                                        r[num_players] = UTIL_ToUInt32( Row[9] );


					// Leaver detect
					if( UTIL_ToUInt32( Row[12] ) < ( UTIL_ToUInt32( Row[14] )-500 ) )
						leaver[num_players] = 1;
					else
						leaver[num_players] = 0;

					num_players++;
					Row = MySQLFetchRow( Result );
				}

				mysql_free_result( Result );

				if( !ignore )
				{
					if( num_players == 0 )
						cout << "gameid " << UTIL_ToString( GameID ) << " has no players, ignoring" << endl;
					else if( team_numplayers[0] == 0 )
						cout << "gameid " << UTIL_ToString( GameID ) << " has no Sentinel players, ignoring" << endl;
					else if( team_numplayers[1] == 0 )
						cout << "gameid " << UTIL_ToString( GameID ) << " has no Scourge players, ignoring" << endl;
					else
					{
						cout << "gameid " << UTIL_ToString( GameID ) << " is calculating" << endl;

						for( int i = 0; i < num_players; i++ )
						{
							cout << "player [" << names[i] << "] score " << " -> " << Int32_ToString( nscore[i] ) << endl;

							if( exists[i] )
							{
								string QUpdateScore = "UPDATE `stats` SET leaver = leaver+"+UTIL_ToString(leaver[i])+", banned = "+ UTIL_ToString( banned[i] ) +", zerodeaths = zerodeaths+ "+ UTIL_ToString( zd[i] ) +", maxlosingstreak = " + UTIL_ToString( maxlstreak[i] ) + ", maxstreak = " + UTIL_ToString( maxstreak[i] ) + ", "+ lstreak[i] + streak[i] +" wins = wins+" + UTIL_ToString( win[i] ) + ", losses = losses+" + UTIL_ToString( losses[i] ) + ", draw = draw+" + UTIL_ToString( draw[i] ) + ", "+ score[i] +" games= games+1, kills=kills+" + UTIL_ToString( k[i] ) + ", deaths=deaths+" + UTIL_ToString( d[i] ) + ", assists=assists+" + UTIL_ToString( a[i] ) + ", creeps=creeps+" + UTIL_ToString( c[i] ) + ", denies=denies+" + UTIL_ToString( de[i] ) + ", neutrals=neutrals+" + UTIL_ToString( n[i] ) + ", towers=towers+" + UTIL_ToString( t[i] ) + ", rax=rax+" + UTIL_ToString( r[i] ) + ",  ip= '" + ips[i] + "' WHERE id=" + UTIL_ToString( rowids[i] );

								if( mysql_real_query( Connection, QUpdateScore.c_str( ), QUpdateScore.size( ) ) != 0 )
								{
									cout << "error: " << mysql_error( Connection ) << endl;
									return 1;
								}
							}
							else
							{
								string EscName = MySQLEscapeString( Connection, names[i] );
								string EscLName = MySQLEscapeString( Connection, lnames[i] );
								string EscServer = MySQLEscapeString( Connection, servers[i] );
								string QInsertScore = "INSERT INTO `stats` ( player, player_lower, banned, realm, ip, score, games, kills, deaths, assists, creeps, denies, neutrals, towers, rax, wins, losses, draw, streak, maxstreak, losingstreak, maxlosingstreak, zerodeaths, leaver ) VALUES ( '" + EscName + "', '" + EscLName + "', '" + UTIL_ToString( banned[i] ) + "', '" + EscServer + "', '" + ips[i] + "', "+ Int32_ToString( nscore[i] ) +", 1, " + UTIL_ToString( k[i]) + ", " + UTIL_ToString( d[i]) + ", " + UTIL_ToString( a[i]) + ", " + UTIL_ToString( c[i]) + ", " + UTIL_ToString( de[i]) + ", " + UTIL_ToString( n[i]) + ", " + UTIL_ToString( t[i]) + ", " + UTIL_ToString( r[i]) + ", " + UTIL_ToString( win[i]) + ", " + UTIL_ToString( losses[i]) + ", " + UTIL_ToString( draw[i]) + ", " + UTIL_ToString( nstreak[i]) + ", " + UTIL_ToString( maxstreak[i]) + ", " + UTIL_ToString( nlstreak[i]) + ", " + UTIL_ToString( maxlstreak[i]) + ", " + UTIL_ToString( zd[i]) + ", " + UTIL_ToString( leaver[i]) + " )";

								if( mysql_real_query( Connection, QInsertScore.c_str( ), QInsertScore.size( ) ) != 0 )
								{
									cout << "error: " << mysql_error( Connection ) << endl;
									return 1;
								}
							}
						}
					}
				}
			}
			else
			{
				cout << "error: " << mysql_error( Connection ) << endl;
				return 1;
			}
		}

                string QInsertScored1 = "UPDATE `games` SET `stats` = '1' WHERE `id` = " + UTIL_ToString( GameID ) + ";";

                if( mysql_real_query( Connection, QInsertScored1.c_str( ), QInsertScored1.size( ) ) != 0 )
                {
                        cout << "error: " << mysql_error( Connection ) << endl;
                        return 1;
                }

	}
	cout << "committing transaction" << endl;

	string QCommit = "COMMIT";

	if( mysql_real_query( Connection, QCommit.c_str( ), QCommit.size( ) ) != 0 )
	{
		cout << "error: " << mysql_error( Connection ) << endl;
		return 1;
	}

	cout << "done" << endl;
	return 0;
}
