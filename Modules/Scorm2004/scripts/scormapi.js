/**
 * ILIAS Open Source
 * --------------------------------
 * Implementation of ADL SCORM 2004
 * 
 * This program is free software. The use and distribution terms for this software
 * are covered by the GNU General Public License Version 2
 * 	<http://opensource.org/licenses/gpl-license.php>.
 * By using this software in any fashion, you are agreeing to be bound by the terms 
 * of this license.
 * 
 * Note: This code derives from other work by the original author that has been
 * published under Common Public License (CPL 1.0). Please send mail for more
 * information to <alfred.kohnert@bigfoot.com>.
 * 
 * You must not remove this notice, or any other, from this software.
 * 
 * PRELIMINARY EDITION
 * This is work in progress and therefore incomplete and buggy ...
 * 
 * Derived from OpenPALMS Humbaba
 * An ECMAScript/JSON Implementation of the ADL-RTE Version API.
 * 
 * Content-Type: application/x-javascript; charset=ISO-8859-1
 * Modul: ADL SCORM Generic Runtime API Implementation
 *  
 * @author Alfred Kohnert <alfred.kohnert@bigfoot.com>
 * @version $Id$
 * @copyright: (c) 2005-2007 Alfred Kohnert
 */ 

/**
 * ADL SCORM RTE API constructor 
 * @param {object} required; a copy (not a reference) of item in CmiDataStore
 * 	it has to be of prototype CMIData13
 * @param {object} required; start values for LMS specific cmi data elements 
 * 	like e.g. cmi.learner_id etc.  
 * @param {function} required; listener for commit, to update CmiDataStore
 * 	a function that take modified CMIData13 as input 
 * @param {function} optional; listener for debug information generated by API
 * 	should of following signature 
 * 		function(methodName, methodArgs, methodReturn, error, diagnostic, debugMessage)
 * 			methodReturn can be passed through or altered for API return 
 **/
function OP_SCORM_RUNTIME(cmiItem, onCommit, onTerminate, onDebug)  
{ 
 
	// private constants: API states
	var NOT_INITIALIZED = 0;
	var RUNNING = 1;
	var TERMINATED = 2;

	// private constants: permission
	var READONLY  = 1;
	var WRITEONLY = 2;
	var READWRITE = 3;

	// private properties
	var state = NOT_INITIALIZED;
	var error = 0;
	var diagnostic = '';
	var dirty = false;
	var msec = null; 
	var me = this; // reference to API for use in methods
	
	var events = 
	{
		'Initialized' : [], 
		'Terminated' : [], 
		'Committed' : []
	};
	
	// possible public methods
	var methods = 
	{
		'Initialize' : Initialize,
		'Terminate' : Terminate,
		'GetValue' : GetValue,
		'SetValue' : SetValue,
		'Commit' : Commit,
		'GetLastError' : GetLastError,
		'GetErrorString' : GetErrorString,
		'GetDiagnostic' : GetDiagnostic,
		'LMSAddEventListener' : AddEventListener,
		'LMSRemoveEventListener' : RemoveEventListener
	};
		
	// bind public methods 
	for (var k in me.methods) 
	{
		me[k] = methods[k];
	}
	
	// implementation of public methods
	
	// public error code property getter
	function GetLastError() 
	{
		return error.toString();
	}

	/**
	 * public error description property getter
	 * error codes and descriptions (see "SCORM Run-Time Environment
	 * Version 1.3" on www.adlnet.org)
	 * @param {string} error number must be string!
	 */	 
	function GetErrorString(param) 
	{
		if (typeof param !== 'string') 
		{
			return setReturn(201, 'GetError param must be empty string', '');
		}
		var e = me.errors[param]; 
		return e ? e.message : '';
	}

	/**
	 * public error details getter 
	 * my be useful in debugging
	 * @param {string} required; but not evaluated in this implementation 
	 */	 
	function GetDiagnostic(param) 
	{
		return typeof(param)==='string' && error ? diagnostic : '';
	}

	/**
	 * Open connection to data provider 
	 * @access private, but also bound to 'this'
	 * @param {string} required; must be '' 
	 */	 
	function Initialize(param) 
	{
		setReturn(-1, 'Initialize(' + param + ')');
		if (param!=='') 
		{
			return setReturn(201, 'param must be empty string', 'false');
		}
		switch (state) 
		{
			case NOT_INITIALIZED:
				dirty = false;
				if (cmiItem instanceof Object) 
				{
					state = RUNNING;
					msec = (new Date()).getTime();
					doEvents('Initialized');
					return setReturn(0, '', 'true');
				} 
				else 
				{
					return setReturn(101, '', 'false');
				}
				break;
			default:
				return setReturn(103, '', 'false');
		}
		return setReturn(103, '', 'false');
	}	

	/**
	 * Sending changes to data provider 
	 * @access private, but also bound to 'this'
	 * @param {string} required; must be '' 
	 */	 
	function Commit(param) 
	{
		setReturn(-1, 'Commit(' + param + ')');
		if (param!=='') 
		{
			return setReturn(201, 'param must be empty string', 'false');
		}
		var r;
		switch (state) 
		{
			case NOT_INITIALIZED:
				r = setReturn(142, '', 'false');
				break;
			case TERMINATED:
				r = setReturn(143, '', 'false');
				break;
			case RUNNING:
				var returnValue = onCommit(cmiItem);
				if (returnValue) 
				{
					dirty = false;
					doEvents('Committed');
					r = setReturn(0, '', 'true');
				} 
				else
				{
					r = setReturn(301, '', 'false');
				}
				break;
		}
		return r;
	}

	/**
	 * Close connection to data provider 
	 * @access private, but also bound to 'this'
	 * @param {string} required; must be '' 
	 */	 
	function Terminate(param) {
		setReturn(-1, 'Terminate(' + param + ')');
		if (param!=='') 
		{
			return setReturn(201, 'param must be empty string', 'false');
		}
		var r;
		switch (state) 
		{
			case NOT_INITIALIZED:
				r = setReturn(112, '', 'false');
				break;
			case TERMINATED:
				r = setReturn(113, '', 'false');
				break;
			case RUNNING:
				me.onTerminate(getValue, setValue, msec);
				setReturn(-1, 'Terminate(' + param + ') [after wrapup]');
				var returnValue = Commit('');
				state = TERMINATED;
				onTerminate(cmiItem);
				doEvents('Terminated');
				r = setReturn(0, '', returnValue);
				break;
		}
		return r;
	}
	
	/**
	 * Read data element entry 
	 * @access private, but also bound to 'this'
	 * @param {string} required; must be valid cmi element string 
	 */	 
	function GetValue(sPath) 
	{
		setReturn(-1, 'GetValue(' + sPath + ')');
		var r;
		switch (state) 
		{
			case NOT_INITIALIZED:
				r = setReturn(122, '', '');
				break;
			case TERMINATED:
				r = setReturn(123, '', '');
				break;
			case RUNNING:
				if (typeof(sPath)!=='string') 
				{
					r = setReturn(201, 'must be string', '');
				}
				else
				{
					r = getValue(sPath, false);
					r = error ? '' : setReturn(0, '', r); 
				}
				break;
				// TODO wrap in TRY CATCH
		}	
		return r;
	}
	
	/**
	 * Read data element entry 
	 * @access private
	 * @param {string} required; must be valid cmi element string 
	 * @param {boolean} optional; if true all permissions are assumed as readwrite 
	 */
	function getValue(path, sudo) 
	{
		var tokens = path.split('.');
		return walk(cmiItem, me.models[tokens[0]], tokens, null, sudo, {parent:[]});
	}	

	/**
	 * Update or create data element entry 
	 * @access private, but also bound to 'this'
	 * @param {string} required; must be valid cmi element string
	 * @param {string} required; must be valid cmi element value
	 */	  
	function SetValue(sPath, sValue) 
	{
		setReturn(-1, 'SetValue(' + sPath + ', ' + sValue + ')');
		var r;
		switch (state) 
		{
			case NOT_INITIALIZED:
				r = setReturn(132, '', '');
				break;
			case TERMINATED:
				r = setReturn(133, '', '');
				break;
			case RUNNING:
				if (typeof(sPath)!=='string') 
				{
					r = setReturn(201, 'must be string', '');
					break;
				}
				// we do not test datatype for there are to many scorm editors out there
				// that do send numerics in there APIWrappers that we cast input
				// as if we were an applet or object implementation
				if (typeof sValue === "number") 
				{
					sValue = sValue.toFixed(3);
				}
				else
				{ 
					sValue = String(sValue);
				}
				try 
				{
					r = setValue(sPath, sValue);
					r = error ? 'false' : 'true'; 
				} 
				catch (e) 
				{
					r  = setReturn(351, 'Exception ' + e, 'false');
				}
				break;
		}
		return r;
	}
	
	/**
	 * Update or create data element entry
	 * @access private
	 * @param {string} required; must be valid cmi element string 
	 * @param {string} required; must be valid cmi element value
	 * @param {boolean} optional; if true all permissions are assumed as readwrite 
	 */
	function setValue(path, value, sudo) 
	{
		var tokens = path.split('.');
		return walk(cmiItem, me.models[tokens[0]], tokens, value, sudo, {parent:[]});
		
	}	
	
	/**
	 * Synchronized walk on data instance and data model to read/replace content
	 * @access private
	 * @param {object} required; data instance node 
	 * @param {object} required; data model node
	 * @param {array} required; path of tokens to walk down ["cmi", "core", "etc"] 
	 * @param {object} optional; new value for setValue
	 * @param {boolean} optional; if true walks in superuser mode, i.e. ignore permissions 
	 * @param {object} optional; temporary data stored for use in deeper evaluations, used for some context dependencies 
	 */
	function walk(dat, def, path, value, sudo, extra) 
	{
		var setter, token, result, tdat, tdef, k, token2, tdat2;
		 
		setter = typeof value === "string";

		token = path.shift();
		
		if (!def) 
		{
			return setReturn(401, 'Unknown:' + token, 'false');
		}

		tdat = dat[token];
		tdef = def[token];
		
		if (!tdef) 
		{
			return setReturn(401, 'Unknown:' + token, 'false');
		}
		
		if (tdef.type == Function) // adl.nav.request.choice ... target=blabla 
		{
			token2 = path.shift();
			result = tdef.children.type.getValue(token2, tdef.children); 
			return setReturn(0, '', result);
		}
		
		if (path[0] && path[0].charAt(0)==="_") 
		{
			if (path.length>1) return setReturn(401, '', '');
			if (setter) return setReturn(404, '', false);
			
			if ('_children' === path[0]) 
			{
				if (!tdef.children) 
				{
					return setReturn(301, 'Data Model Element Does Not Have Children', '');
				}
				result = []; 
				for (k in tdef.children) 
				{
					result.push(k);
				}  
				return setReturn(0, '', result.join(","));
			}
			
			if ('_count' === path[0]) 
			{
				return tdef.type !== Array
					? setReturn(301, 'Data Model Element Cannot Have Count', '')
					: setReturn(0, '', tdat && tdat.length ? tdat.length : 0);
			}
	
			if (token==="cmi" && '_version' === path[0]) 
			{
				return setReturn(0, '', '1.0');
			}
		}

		if (tdef.type == Array) // checks two tokens in one step e.g. "interactions" and "1"
		{
			token2 = path.shift();
			if (!(/^\d+$/).test(token2))
			{
				return setReturn(401, '');
			}
			token2 = Number(token2);
			tdat = tdat ? tdat : new Array();
			tdat2 = tdat[token2];
			if (setter)
			{
				if (token2 > tdat.length) 
				{
					return setReturn(351, 'Data Model Element Collection Set Out Of Order');
				}
				if (tdef.maxOccur && token2+1 > tdef.maxOccur) 
				{
					return setReturn(301, '');
				}
				if (tdat2 === undefined) 
				{
					tdat2 = new Object();
				}
				if (unique in tdef && tdef.unique===path[path.length-1])
				{
					for (var di=tdat.length; di--;) 
					{
						if (di!==token2 && tdat[di][tdef.unique]===value) 
						{
							return setReturn(351, 'must be unique');
						}
					}
				}
				extra.parent.push(dat);
				result = walk(tdat2, tdef.children, path, value, sudo, extra);
				if (!error) 
				{
					tdat[token2] = tdat2;
					dat[token] = tdat;
				}
				return result;
			}
			else if (tdat2)
			{
				return walk(tdat2, tdef.children, path, value, sudo, extra);
			}
			else
			{
				return setReturn(301, 'Data Model Collection Element Request Out Of Range');
			}
		}
		
		if (tdef.type == Object)
		{
			if (tdat === undefined)
			{
				if (setter)
				{
					tdat = new Object();
					extra.parent.push(dat);
					result = walk(tdat, tdef.children, path, value, sudo, extra);
					if (!error) 
					{
						dat[token] = tdat;
					}
					return result;
				}
				else
				{
					return setReturn(401, token + ' not found');
				}
			}
			else
			{
				if (setter) {
					extra.parent.push(dat);
				}
				return walk(tdat, tdef.children, path, value, sudo, extra);
			}
		}
		
		if (setter)
		{
			if (tdef.permission === READONLY && !sudo) 
			{
				return setReturn(404, 'readonly:'+token);
			}
			if (path.length)  
			{ 
				return setReturn(401	, 'no children allowed to this');
			}
			if (tdef.dependsOn) {
				extra.parent.push(dat);
				var dep = tdef.dependsOn.split(" ");
				for (var di=dep.length; di--;) 
				{
					var dj = extra.parent.length-1;
					var dp = dep[di].split(".");
					var dpar = extra.parent;
					if (dpar[dpar.length-dp.length][dp.pop()]===undefined)
					{
						return setReturn(408, 'dependency on ..' + dep[di]);
					}
				}
			}
			if (!tdef.type.isValid(value, tdef, extra))
			{
				if (extra.error) 
				{
					return setReturn(extra.error.code, extra.error.diagnostic);
				}
				else
				{
					return setReturn(406, 'value not valid');
				}
			} 
			dat[token] = value;
			dirty = true;
			return setReturn(0, '');
		}
		else // getter
		{
			if (tdef.permission === WRITEONLY && !sudo) 
			{
				return setReturn(405, 'writeonly:' + token);
			}
			else if (path.length)  
			{ 
				return setReturn(401, 'no children allowed');
			}
			else if (typeof tdat !== "string")
			{
				if (tdef['default']) 
				{
					return setReturn(0, '', tdef['default']);
				}
				else
				{
					return setReturn(403, 'not initialized ' + token);
				}
			} 
			else
			{
				return setReturn(0, '', tdat.toString());
			}
		}
	}
	
	/**
	 * Append an event
	 * @param {string} required; must be valid event name 
	 * @param {function} required; handler (a function without parameters)
	 * @return {string} true if handler was added 
	 */
	function AddEventListener(type, listener) 
	{
		var event = events[type];
		if (!event) 
		{
			return 'false';
		}
		if (typeof(listener) === 'string') 
		{
			listener = window[listener];
		}
		if (typeof(listener)!=='function') 
		{
			return setReturn(0, '', 'false');
		}
		event.push(listener);
		return 'true';
	}

	/**
	 * Delete an event
	 * call with exactly the same parameters as AddEventListener	 
	 * @param {string} required; must be valid event name 
	 * @param {function} required; handler (a function without parameters)
	 * @return {string} true if handler was removed 
	 */
	function RemoveEventListener(type, listener) 
	{
		var event = events[type];
		if (!event) 
		{
			return 'false';
		}
		if (typeof(listener)==='string') 
		{
			listener = window[listener];
		}
		if (typeof(listener)!=='function') 
		{
			return setReturn(0, '', 'false');
		}
		for (var i=event.length-1; i>-1; i-=1) 
		{
			if (event[i]===listener) 
			{
				delete event[i];
				break;
			} 
		}
		return (i>-1).toString();
	}

	/**
	 * run registered listeners on a event
	 * exceptions are caught, so you may not know if an event was successfully 
	 * executed, in debug mode you will be informed by onDebug handler
	 *	@access private
	 * @param {string} required; must be valid event name 
	 */	 
	function doEvents(type) 
	{
		var event = events[type];
		for (var i=event.length-1; i>-1; i-=1) 
		{
			try {
				event[i]();
			} 
			catch(e) 
			{
				setReturn(error, diagnostic);
			}
		};
	}

	/**
	 *	@access private
	 *	@param {number}  
	 *	@param {string}  
	 *	@param {string}  
	 *	@return {string} 
	 */	 
	function setReturn(errCode, errInfo, returnValue) 
	{
		if (errCode>-1 && typeof onDebug === 'function') 
		{
			var newReturnValue = onDebug(diagnostic, returnValue, errCode, errInfo, cmiItem);
			if (errCode===-1) 
			{
				return undefined; 
			} 
			else if (newReturnValue!==undefined) 
			{
				returnValue = newReturnValue;
			} 
		}
		error = errCode;
		diagnostic = (typeof(errInfo)=='string') ? errInfo : '';
		return returnValue;
	}
}
