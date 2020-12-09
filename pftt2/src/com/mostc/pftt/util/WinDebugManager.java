package com.mostc.pftt.util;

import java.nio.charset.Charset;
import java.util.Map;

import com.github.mattficken.io.StringUtil;
import com.mostc.pftt.host.AHost;
import com.mostc.pftt.host.AHost.ExecHandle;
import com.mostc.pftt.host.ExecOutput;
import com.mostc.pftt.host.Host;
import com.mostc.pftt.model.core.PhpBuild;
import com.mostc.pftt.results.ConsoleManager;
import com.mostc.pftt.results.ConsoleManagerUtil;
import com.mostc.pftt.results.EPrintType;
import com.mostc.pftt.runner.AbstractTestPackRunner.TestPackRunnerThread;
import com.mostc.pftt.scenario.ScenarioSet;

/** handles integrating with WinDebug.
 * 
 * provides WinDebug with
 * -debug symbols
 * -source code
 * -build image
 * -misc default settings
 * -titles the windebug window with the test case(s) that were run
 * 
 * For more windebug information:
 * @see http://msdn.microsoft.com/en-us/library/windows/hardware/ff542967%28v=vs.85%29.aspx
 * @see http://msdn.microsoft.com/en-us/library/ms680360.aspx
 * 
 * @author Matt Ficken
 *
 */

public class WinDebugManager extends WindowsDebuggerToolsManager {
	private String win_dbg_exe;
	private AHost win_dbg_host;
	private boolean displayed_windbg_tips = false;
	
	protected void ensureFindWinDbgExe(ConsoleManager cm, AHost host, PhpBuild build) {
		if (this.win_dbg_host==host)
			return;
		
		this.win_dbg_host = host;
		this.win_dbg_exe = findWinDebugExe(host, build);
		
		if (StringUtil.isEmpty(this.win_dbg_exe))
			cm.println(EPrintType.SKIP_OPERATION, getClass(), "WinDebug not found. Install WinDebug to any: "+StringUtil.toString(getWinDebugPaths(host, build)));
		else
			this.win_dbg_exe = StringUtil.ensureQuoted(this.win_dbg_exe);
	}
	
	@Override
	public WinDebug newDebugger(ConsoleManager cm, AHost host, ScenarioSet scenario_set, Object server_name, PhpBuild build, int process_id, ExecHandle process) {
		ensureFindWinDbgExe(cm, host, build);
		
		WinDebug dbg = null;
		ensureFindSourceAndDebugPack(cm, host, scenario_set, build);
		
		try {
			dbg = new WinDebug(host, win_dbg_exe, toServerName(server_name), src_path, debug_path, build.getBuildPath(), process_id, process);
		} catch ( Exception ex ) {
			cm.addGlobalException(EPrintType.OPERATION_FAILED_CONTINUING, getClass(), "newDebugger", ex, "", host, win_dbg_exe);
		}
		
		if (dbg != null && dbg.attached) {
			if (!displayed_windbg_tips) {
				displayed_windbg_tips = true;
				
				if (cm!=null)
					displayWindebugTips(cm);
			}
		}
		
		return dbg;
	}
	
	protected void displayWindebugTips(ConsoleManager cm) {
		cm.println(EPrintType.TIP, getClass(), "  WinDebug Command Referrence: http://www.windbg.info/doc/1-common-cmds.html");
		cm.println(EPrintType.TIP, getClass(), "  WinDebug command: k                       - show callstack");
		cm.println(EPrintType.TIP, getClass(), "  WinDebug command: g                       - go (until next exception)");
		cm.println(EPrintType.TIP, getClass(), "  WinDebug command: .dump /ma <filename>    - create coredump file");
		cm.println(EPrintType.TIP, getClass(), "  WinDebug command: <F9>                    - set breakpoint");
	}

	public static class WinDebug extends Debugger {
		protected ExecHandle debug_handle, process;
		protected final String log_file;
		protected final AHost host;
		protected boolean attached, wait;
		
		protected WinDebug(AHost host, String win_dbg_exe, String server_name, String src_path, String debug_path, String image_path, int process_id, ExecHandle process) throws Exception {
			this.host = host;
			this.process = process;
			
			log_file = host.mCreateTempName(getClass(), ".log");
			
			//
			// generate windebug command (with lots of extra options, etc...)
			// @see http://msdn.microsoft.com/en-us/library/windows/hardware/ff561306%28v=vs.85%29.aspx
			StringBuilder sb = new StringBuilder();
			sb.append(win_dbg_exe);
			// -g => run debuggee immediately after attaching
			sb.append(" -g");
			// -p => PID of debuggee - do first in case command gets cut short
			sb.append(" -p ");sb.append(process_id);
			// -T => set window title => server name (usually test case names) t
			sb.append(" -T \"");sb.append(server_name);sb.append("\"");
			// -y => provide directory with debug symbol .pdb files
			if (StringUtil.isNotEmpty(debug_path)) {
				sb.append(" -y \"");sb.append(host.fixPath(debug_path));sb.append("\"");
			}
			// -srcpath => provide source code
			if (StringUtil.isNotEmpty(src_path)) {
				sb.append(" -srcpath ");sb.append(host.fixPath(src_path));
			}
			// -i => provide path to executable image
			sb.append(" -i \"");sb.append(host.fixPath(image_path));sb.append("\"");
			// -logo => log output to file
			sb.append(" -logo \"");sb.append(host.fixPath(log_file));sb.append("\"");
			// -QY => suppress save workspace dialog (don't change workspace file)
			sb.append(" -QY ");
			// -n => noisy symbol load => provide extra info about symbol loading to trace any symbol problems
			sb.append(" -n");
			// -WF => provide default workspace file, which will automatically dock the command window within the windebug window
			String workspace_file = host.fixPath(host.joinIntoOnePath(host.getPfttBinDir(), "\\pftt_workspace.WEW"));
			if (host.mExists(workspace_file)) {
				sb.append(" -WF \"");sb.append(workspace_file);sb.append("\"");
			}
			//
			
			// now execute windebug
			debug_handle = host.execThread(sb.toString());
			
			
			// wait for log file to be created and reach a minimum size
			// then, assume that the debugger is attached (give up waiting after too long though)
			wait = true;
			for ( int i=0 ; i < 500 && wait ; i++ ) {
				Thread.sleep(100);
				if ( host.mSize(log_file) > 800 ) {
					attached = true;
					wait = false;
					break;
				}
			}
		}
		
		@Override
		public void close(ConsoleManager cm) {
			if (debug_handle.isRunning()&&(process.isCrashed()||process.isRunning())) {
				// if it has crashed, wait for user to manually close the debugger
				// if it hasn't crashed yet (still running, might crash) wait to close the debugger
				return;
			}
			 
			debug_handle.close(cm, true);
			
			wait = false;
			
			try {
				host.mDelete(log_file);
			} catch (Exception e) {
				ConsoleManagerUtil.printStackTrace(WinDebugManager.class, cm, e);
			}
		}

		@Override
		public boolean isRunning() {
			return debug_handle.isRunning();
		}

		@Override
		public boolean exec(ConsoleManager cm, String ctx_str,
				String commandline, int timeout, Map<String, String> env,
				byte[] stdin, Charset charset, String chdir,
				TestPackRunnerThread thread, int thread_slow_sec)
				throws Exception {
			// TODO Auto-generated method stub
			return false;
		}

		@Override
		public ExecOutput execOut(String cmd, int timeout_sec,
				Map<String, String> object, byte[] stdin_post, Charset charset)
				throws IllegalStateException, Exception {
			// TODO Auto-generated method stub
			return null;
		}

		@Override
		public RunRequest createRunRequest(ConsoleManager cm, String ctx_str) {
			// TODO Auto-generated method stub
			return null;
		}
		
		@Override
		public ExecOutput execOut(RunRequest req) {
			// TODO Auto-generated method stub
			return null;
		}
		
		@Override
		public ExecHandle execThread(RunRequest req) {
			// TODO Auto-generated method stub
			return null;
		}

		@Override
		public ExecHandle execThread(String commandline,
				Map<String, String> env, String chdir, byte[] stdin_data)
				throws Exception {
			// TODO Auto-generated method stub
			return null;
		}
		
	} // end public static class WinDebug
	
	/** returns the file paths that are checked for WinDebug.
	 * 
	 * windebug must be installed to one of these paths.
	 * 
	 * @param host
	 * @param build
	 * @return
	 */
	public static String[] getWinDebugPaths(Host host, PhpBuild build) {
		return getToolPaths(host, build, "windbg.exe");
	}
	
	/** returns the path that WinDebug is installed at, or returns null if windebug is not found.
	 * 
	 * @see #getWinDebugPaths
	 * @param host
	 * @return
	 */
	public static String findWinDebugExe(Host host, PhpBuild build) {
		return host.anyExist(getWinDebugPaths(host, build));
	}

	public static boolean checkIfWinDebugInstalled(AHost host, PhpBuild build) {
		String win_dbg_exe = findWinDebugExe(host, build);
		
		if (StringUtil.isEmpty(win_dbg_exe)) {
			
			
			System.err.println("PFTT: -debug_all  or -debug_list console option given but WinDebug is not installed");
			
			if (build.isX86()) {
				System.err.println("PFTT: you MUST install the x86 edition of WinDebug - found x64 edition but debugging an x86 Binary Build requires the x86 WinDebug");
			} else {
				System.err.println("PFTT: you MUST install the x64 edition of WinDebug - found x86 edition but debugging an x64 Binary Build requires the x64 WinDebug");
			}
			
			System.err.println("PFTT: searched for WinDebug at these locations: "+StringUtil.toString(WinDebugManager.getWinDebugPaths(host, build)));
			System.err.println("PFTT: install WinDebug or remove -debug_all or -debug_list console option");
			System.err.println("PFTT: download WinDebug here: http://msdn.microsoft.com/en-us/windows/hardware/gg463009.aspx");
			return false;
		}
		return true;
	}
	
} // end public class WinDebugManager
