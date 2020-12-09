package com.mostc.pftt.scenario;

import com.mostc.pftt.host.Host;
import com.mostc.pftt.model.core.PhpBuild;
import com.mostc.pftt.results.ConsoleManager;

/** NOT IMPLEMENTED
 * 
 * @see UNCPathsScenario
 * @author Matt Ficken
 *
 */

public class NormalPathsScenario extends PathsScenario {
	
	@Override
	public boolean isPlaceholder(EScenarioSetPermutationLayer layer) {
		return true;
	}

	@Override
	public IScenarioSetup setup(ConsoleManager cm, FileSystemScenario fs, Host host, PhpBuild build, ScenarioSet scenario_set, EScenarioSetPermutationLayer layer) {
		// TODO Auto-generated method stub
		return SETUP_FAILED;
	}

	@Override
	public String getName() {
		return "Normal-Paths";
	}

	@Override
	public boolean isImplemented() {
		return true;
	}

}
