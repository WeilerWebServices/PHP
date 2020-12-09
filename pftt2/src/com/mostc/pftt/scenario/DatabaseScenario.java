package com.mostc.pftt.scenario;

import java.sql.Connection;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.sql.Statement;
import java.util.LinkedList;
import java.util.Map;
import java.util.Random;

import com.github.mattficken.Overridable;
import com.github.mattficken.io.StringUtil;
import com.mostc.pftt.host.AHost;
import com.mostc.pftt.host.Host;
import com.mostc.pftt.model.core.PhpBuild;
import com.mostc.pftt.model.core.PhpIni;
import com.mostc.pftt.results.ConsoleManager;
import com.mostc.pftt.results.ConsoleManagerUtil;
import com.mostc.pftt.results.EPrintType;
import com.mostc.pftt.runner.AbstractPhpUnitTestCaseRunner;
import com.mostc.pftt.util.TimerUtil;
import com.mostc.pftt.util.TimerUtil.ObjectRunnable;
import com.mostc.pftt.util.TimerUtil.WaitableRunnable;

/** A Scenario that sets up a database service for (an) extension(s) to test.
 * 
 * @author Matt Ficken
*
*/

public abstract class DatabaseScenario extends NetworkedServiceScenario {
	protected final AHost host;
	protected final String default_username, default_password;
	protected final LinkedList<DatabaseScenarioSetup> setups;
	protected final IDatabaseVersion version;
	protected static final Object production_setup_lock = new Object();
	protected DatabaseScenarioSetup production_setup;
	
	public DatabaseScenario(IDatabaseVersion version, AHost host, String default_username, String default_password) {
		this.version = version;
		this.host = host;
		this.default_username = default_username;
		this.default_password = default_password;
		
		setups = new LinkedList<DatabaseScenarioSetup>();
	}
	
	public String getDefaultPassword() {
		return default_password;
	}
	
	public String getDefaultUsername() {
		return default_username;
	}
	
	@Override
	public String getName() {
		return version.getNameWithVersionInfo();
	}
	
	@Override
	public boolean ignoreForShortName(EScenarioSetPermutationLayer layer) {
		if (layer==null)
			return false;
		switch(layer) {
		case FUNCTIONAL_TEST_APPLICATION:
		case FUNCTIONAL_TEST_DATABASE:
		case PRODUCTION_OR_ALL_UP_TEST:
			return false;
		default:
			return version == null || version.isAny();
		}
	}
	
	@Override
	public boolean isPlaceholder(EScenarioSetPermutationLayer layer) {
		if (layer==null)
			return false;
		switch(layer) {
		case FUNCTIONAL_TEST_APPLICATION:
		case FUNCTIONAL_TEST_DATABASE:
		case PRODUCTION_OR_ALL_UP_TEST:
			return false;
		default:
			return version == null || version.isAny();
		}
	}
	
	@Override
	public boolean setupRequired(EScenarioSetPermutationLayer layer) {
		return true;
	}
	
	public IDatabaseVersion getDatabaseVersion() {
		return version;
	}
	
	public interface IDatabaseVersion {
		public String getNameWithVersionInfo();

		public boolean isAny();

		
	}
	
	@Override
	public Class<?> getSerialKey(EScenarioSetPermutationLayer layer) {
		switch(layer) {
		// IMPORTANT: when running a web application, it can only have 1 database scenario
		case PRODUCTION_OR_ALL_UP_TEST:
		case FUNCTIONAL_TEST_DATABASE:
			return DatabaseScenario.class;
		default:
			// whereas, when testing PHP Core, you can run multiple database scenarios at the same time (faster)
			//     the only downside is that you're loading multiple database DLLs (mysql.dll postgres.dll, etc...)
			//     which wouldn't/shouldn't be done in production
			//     -however, when changing which DLLs are loaded, problems are only likely introduced when removing a DLL or changing order
			//       so this is ok (trading this for substantial speed increase)
			//
			//
			// this also handles if multiple versions of the same database scenario are being permuted
			//  ... there will be 1 version for each ScenarioSet
			return super.getSerialKey(layer);
		}
	}
	
	@Override
	public boolean isUACRequiredForSetup() {
		return true;
	}
	
	protected abstract String getDriverClassName();
	
	protected boolean ensureDriverLoaded() {
		try {
			Class.forName(getDriverClassName());
			return true;
		} catch ( Exception ex ) {
			ConsoleManagerUtil.printStackTrace(DatabaseScenario.class, ex);
		}
		return false;
	}
	
	@Override
	public DatabaseScenarioSetup setup(ConsoleManager cm, FileSystemScenario fs, Host host, PhpBuild build, ScenarioSet scenario_set, EScenarioSetPermutationLayer layer) {
		final boolean is_production_database_server = layer==EScenarioSetPermutationLayer.PRODUCTION_OR_ALL_UP_TEST;
		if (is_production_database_server) {
			synchronized(production_setup_lock) {
				if (production_setup==null)
					return production_setup;
				
				return production_setup = doSetup(cm, host, build, scenario_set, layer, is_production_database_server);
			}
		} else {
			// reuse existing setup if one is currently running
			if (setups.size() > 0) {
				// randomly distribute
				Random r = new Random();
				DatabaseScenarioSetup s;
				for ( int attempts=0 ; attempts < 5 ; attempts++ ) {
					synchronized (setups) {
						if (setups.size()<1)
							break;
						s = setups.get(r.nextInt(setups.size()));
					}
					if (s.isRunning()) {
						cm.println(EPrintType.CLUE, getClass(), "Reusing existing MySQL server");
						// TODO comment
						ProxyDatabaseScenarioSetup p = new ProxyDatabaseScenarioSetup(s);
						s.proxies.add(p);
						return p;
					}
				}
			}
			return doSetup(cm, host, build, scenario_set, layer, is_production_database_server);
		}
	}
	
	protected class ProxyDatabaseScenarioSetup extends DatabaseScenarioSetup {
		protected final DatabaseScenarioSetup r;
		
		public ProxyDatabaseScenarioSetup(DatabaseScenarioSetup r) {
			this.r = r;
		}

		@Override
		public String getNameWithVersionInfo() {
			return r.getNameWithVersionInfo();
		}
		@Override
		public String getName() {
			return r.getName();
		}
		@Override
		protected boolean connect(ConsoleManager cm) {
			// doesn't get called
			return false;
		}
		@Override
		protected boolean disconnect() {
			// don't call r.disconnect  ... r.close() will do that if/when it should
			return true; 
		}
		@Override
		public boolean databaseExists(String db_name) {
			return r.databaseExists(db_name);
		}
		@Override
		public boolean isRunning() {
			return r.isRunning();
		}
		@Override
		protected boolean startServer(ConsoleManager cm, boolean is_production_database_server) {
			return false;
		}
		@Override
		protected boolean stopServer(ConsoleManager cm, boolean is_production_database_server) {
			return false; // doesn't get called
		}
		@Override
		public boolean prepareINI(ConsoleManager cm, FileSystemScenario fs, AHost host, PhpBuild build, ScenarioSet scenario_set, PhpIni ini) {
			return r.prepareINI(cm, fs, host, build, scenario_set, ini);
		}
		@Override
		public String getPdoDbType() {
			return r.getPdoDbType();
		}
		@Override
		public String getPassword() {
			return r.getPassword();
		}
		@Override
		public String getUsername() {
			return r.getUsername();
		}
		@Override
		public String getHostname() {
			return r.getHostname();
		}
		@Override
		public int getPort() {
			return r.getPort();
		}
		@Override
		public String getDataSourceName() {
			return r.getDataSourceName();
		}
		@Override
		public boolean dropDatabase(String db_name) {
			return r.dropDatabase(db_name);
		}
		@Override
		public boolean createDatabase(String db_name) {
			return r.createDatabase(db_name);
		}
		@Override
		public boolean createDatabaseWithUser(String db_name, String user, String password) {
			return r.createDatabaseWithUser(db_name, user, password);
		}
		@Override
		public boolean createDatabaseReplaceOk(String db_name) {
			return r.createDatabaseReplaceOk(db_name);
		}
		@Override
		public boolean createDatabaseWithUserReplaceOk(String db_name, String user, String password) {
			return r.createDatabaseWithUserReplaceOk(db_name, user, password);
		}
		@Override
		public boolean execute(String sql) {
			return r.execute(sql);
		}
		@Override
		public ResultSet executeQuery(String sql) {
			return r.executeQuery(sql);
		}

		@Override
		protected boolean cleanupServerAfterFailedStarted(ConsoleManager cm, boolean is_production_database_server) {
			return r.cleanupServerAfterFailedStarted(cm, is_production_database_server);
		}

		@Override
		public void getENV(Map<String, String> env) {
			r.getENV(env);
		}
		
	} // end protected class ProxyDatabaseScenarioSetup
	
	public boolean isManaged() {
		// TODO temp for azure
		return true;//false;
	}
	
	protected DatabaseScenarioSetup doSetup(ConsoleManager cm, Host host, PhpBuild build, ScenarioSet scenario_set, EScenarioSetPermutationLayer layer, boolean is_production_database_server) {
		DatabaseScenarioSetup setup = createScenarioSetup(is_production_database_server);
			
		if (setup==null||!ensureDriverLoaded()||(isManaged() && !setup.ensureServerStarted(cm, host, build, scenario_set, layer, is_production_database_server))||!setup.connect(cm))
			return null;
		
		for ( int i=0 ; i < 30 ; i++ ) {
			setup.db_name = generateDatabaseName();
			if (!setup.databaseExists(setup.db_name)) {
				if (setup.createDatabase(setup.db_name)) {
					cm.println(EPrintType.CLUE, getClass(), "Created database: "+setup.db_name);
					synchronized(setups) {
						setups.add(setup);
					}
					return setup;
				}
			}
		}
		cm.println(EPrintType.CANT_CONTINUE, getClass(), "Unable to create database");
		
		return null;
	}
	
	@Overridable
	protected String generateDatabaseName() {
		return "pftt_"+StringUtil.randomLettersStr(5, 10);
	}
	
	protected abstract DatabaseScenarioSetup createScenarioSetup(boolean is_production_server);
	
	public abstract class DefaultUnmanagedDatabaseScenarioSetup extends DefaultDatabaseScenarioSetup {
		
		@Override
		public abstract int getPort();
		
		@Override
		protected boolean startServer(ConsoleManager cm, boolean is_production_database_server) {
			return true;
		}

		@Override
		protected boolean stopServer(ConsoleManager cm, boolean is_production_database_server) {
			return true;
		}

		@Override
		protected boolean cleanupServerAfterFailedStarted(ConsoleManager cm, boolean is_production_database_server) {
			return true;
		}
	}
	
	public abstract class DefaultDatabaseScenarioSetup extends DatabaseScenarioSetup {
		protected Connection connection;
		protected int port;
		
		protected abstract Connection createConnection() throws SQLException;
		
		@Override
		public String getNameWithVersionInfo() {
			return version.getNameWithVersionInfo();
		}
		
		@Override
		public boolean isRunning() {
			if (server_started && connection != null) {
				try {
					return !connection.isClosed();
				} catch ( SQLException ex ) {
					ConsoleManagerUtil.printStackTrace(DatabaseScenario.class, ex);
				}
			}
			return false;
		}
		
		@Override
		protected boolean connect(ConsoleManager cm) {
			Exception ex_out = null;
			for ( int i=0 ; i < 10 ; i++ ) {
				try {
					connection = createConnection();
					if (connection!=null)
						return true;
					Thread.sleep(5000*(i+1)); // 5 10 15
				} catch ( Exception ex ) {
					ConsoleManagerUtil.printStackTrace(DatabaseScenario.class, cm, ex);
					ex_out = ex;
				}
			}
			if (ex_out!=null && cm!=null)
				ConsoleManagerUtil.printStackTrace(EPrintType.CANT_CONTINUE, getClass(), cm, "connect", ex_out, "can't connect to Database server after several tries");
			return false;
		}
		
		@Override
		protected boolean disconnect() {
			if (connection==null)
				return true;
			try {
				connection.close();
				connection = null;
				return true;
			} catch (SQLException ex) {
				ConsoleManagerUtil.printStackTrace(DatabaseScenario.class, ex);
			}
			return false;
		}
		
		@Override
		public String getName() {
			return DatabaseScenario.this.getName();
		}
		
		@Override
		public String getPassword() {
			return DatabaseScenario.this.default_password;
		}

		@Override
		public String getUsername() {
			return DatabaseScenario.this.default_username;
		}
		
		@Override
		public String getHostname() {
			return host.getLocalhostListenAddress();
		}
		
		@Override
		public int getPort() {
			return port;
		}
		
		@Override
		public boolean execute(String sql) {
			try {
				Statement stmt = connection.createStatement();
				System.out.println("sql "+stmt+" "+sql);
				if (stmt!=null) {
					stmt.execute(sql);
					return true;
				}
			} catch (SQLException ex) {
				// TODO temp azure 
				ConsoleManagerUtil.printStackTrace(DatabaseScenario.class, ex);
			}
			return false;
		}
		
		@Override
		public ResultSet executeQuery(String sql) {
			try {
				return connection.createStatement().executeQuery(sql);
			} catch (SQLException ex) {
				ConsoleManagerUtil.printStackTrace(DatabaseScenario.class, ex);
			}
			return null;
		}
		
	} // end public abstract class DefaultDatabaseScenarioSetup
	
	public static boolean empty(ResultSet rs) {
		if (rs==null)
			return false;
		try {
			return rs.next();
		} catch ( SQLException ex ) {
		}
		return false;
	}
	
	public abstract class DatabaseScenarioSetup extends SimpleScenarioSetup {
		protected LinkedList<ProxyDatabaseScenarioSetup> proxies = new LinkedList<ProxyDatabaseScenarioSetup>();
		protected String db_name;
		protected boolean server_started;
		
		protected abstract boolean connect(ConsoleManager cm);
		protected abstract boolean disconnect();
		
		public abstract boolean databaseExists(String db_name);
		
		@Override
		public abstract boolean isRunning();
		
		protected void setupBuild(ConsoleManager cm, AHost host, PhpBuild build, ScenarioSet scenario_set, EScenarioSetPermutationLayer layer) throws IllegalStateException, Exception {
			
		}
		
		protected boolean ensureServerStarted(final ConsoleManager cm, Host host, PhpBuild build, ScenarioSet scenario_set, EScenarioSetPermutationLayer layer, final boolean is_production_database_server) {
			if (server_started)
				return true;
			if (cm != null)
				cm.println(EPrintType.CLUE, getClass(), "Starting database server: "+getNameWithVersionInfo());
			
			try {
				setupBuild(cm, (AHost)host, build, scenario_set, layer);
			} catch ( Exception ex ) {
				cm.addGlobalException(EPrintType.CLUE, getClass(), "ensureServerStarted", ex, "Problem setting up PhpBuild for this database scenario");
			}
			
			WaitableRunnable<Boolean> starth = TimerUtil.runWaitSeconds("DatabaseServerStart", 60, new ObjectRunnable<Boolean>() {
					public Boolean run() {
						return server_started = startServer(cm, is_production_database_server);
					}
				});
			if (!server_started) {
				if (starth.getException()!=null)
					starth.getException().printStackTrace();
				
				// cleanup database server process
				cleanupServerAfterFailedStarted(cm, is_production_database_server);
			}
			return server_started;
		}
		
		protected abstract boolean startServer(ConsoleManager cm, boolean is_production_database_server);
		protected abstract boolean stopServer(ConsoleManager cm, boolean is_production_database_server);
		protected abstract boolean cleanupServerAfterFailedStarted(ConsoleManager cm, boolean is_production_database_server);
		
		private boolean close_called = false;
		@Override
		public final synchronized void close(final ConsoleManager cm) {
			try {
				if (this instanceof ProxyDatabaseScenarioSetup) {
					synchronized(((ProxyDatabaseScenarioSetup)this).r.proxies) {
						((ProxyDatabaseScenarioSetup)this).r.proxies.remove(this);
					}
					// ask real to close (which it will do if this was the last proxy and it was already asked to close)
					((ProxyDatabaseScenarioSetup)this).r.close(cm);
					return;
				}
				if (close_called && proxies.isEmpty()) {
					// now close
				} else if (!proxies.isEmpty()) {
					// wait for all proxies to be closed
					close_called = true;
					return;
				}
				
				if (!(cm.isDebugAll()||cm.isDebugList()||cm.isPfttDebug())) {
					TimerUtil.runThread(new Runnable() {
							public void run() {
								dropDatabase(db_name);
							}
						});
					TimerUtil.trySleepSeconds(2);
				}
				
				synchronized(setups) {
					setups.remove(this);
				}
				cm.println(EPrintType.IN_PROGRESS, getClass(), "Stopping database server...");
				final boolean is_production_server = production_setup == this;
				// sometimes #stopServer can take too long. call it in thread so it can be timed out if it takes too long
				WaitableRunnable<Boolean> r = TimerUtil.runWaitSeconds("DatabaseServerStop", 30, new ObjectRunnable<Boolean>() {
						public Boolean run() {
							return stopServer(cm, is_production_server);
						}
					});
				if (r!=null&&r.getResult()) {
					server_started = false;
					cm.println(EPrintType.CLUE, getClass(), "Stopped database server");
				} else {
					server_started = true;
					cm.println(EPrintType.CLUE, getClass(), "Failed to stop database server");
				}
				// disconnect after stopping the server: sometimes the disconnect process can fail
				//      (sometimes tests really mess up the db server)
				disconnect();
			} catch ( Throwable t ) {
				ConsoleManagerUtil.printStackTrace(DatabaseScenario.class, cm, t);
			}
		}

		/** adds extensions and directives for this Scenario.
		 * 
		 * Typically, you should call PhpIni#addExtensionAndCheck to add extensions to
		 * ensure the extension could be loaded (maybe the DLL/SO is missing or incompatible with this build)
		 * @return 
		 * @throws Exception 
		 * 
		 */
		@Override
		public abstract boolean prepareINI(ConsoleManager cm, FileSystemScenario fs, AHost host, PhpBuild build, ScenarioSet scenario_set, PhpIni ini);
		
		@Override
		public boolean hasENV() {
			return true;
		}
		@Override
		public abstract void getENV(Map<String, String> env);
		
		@Override
		public void setGlobals(Map<String, String> globals) {
			AbstractPhpUnitTestCaseRunner.addDatabaseConnection(
					getDataSourceName(), 
					getHostname(),
					getPort(),
					getUsername(), 
					getPassword(), 
					getDatabaseName(), 
					getPdoDbType(), 
					globals
				);
		}

		public String getDatabaseName() {
			return db_name;
		}
		
		/** PDO driver to use, fe `pdo_mysql` or `pdo_pgsql`
		 * 
		 * @return
		 */
		public abstract String getPdoDbType();
		public abstract String getPassword();
		public abstract String getUsername();
		public abstract String getHostname();
		public abstract int getPort();

		public abstract String getDataSourceName();
		public boolean dropDatabase(String db_name) {
			return execute("DROP DATABASE "+db_name);
		}
		public boolean createDatabase(String db_name) {
			//try {
			//Thread.sleep(10000);
			//} catch ( Exception ex ) {}
			execute("DROP DATABASE IF EXISTS "+db_name); // TODO temp azure
			return execute("CREATE DATABASE "+db_name);
		}
		public abstract boolean createDatabaseWithUser(String db_name, String user, String password);
		public abstract boolean createDatabaseReplaceOk(String db_name);
		public abstract boolean createDatabaseWithUserReplaceOk(String db_name, String user, String password);
		public abstract boolean execute(String sql);
		public abstract ResultSet executeQuery(String sql);
		
	} // end public abstract class DatabaseScenarioSetup
	
} // end public abstract class AbstractDatabaseScenario
