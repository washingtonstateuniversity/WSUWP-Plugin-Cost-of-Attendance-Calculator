( function( $, annualData ) {

	"use strict";

	var dependent = "",
		step = "0",
		dict = {
			aid: {
				"active": false,
				"value": 0,
				"0": "Yes",
				"1": "No"
			},
			age: {
				"active": false,
				"value": 0
			},
			residency: {
				"active": false,
				"value": 0,
				"0": "Eligible for in-state tuition",
				"1": "Eligible for out-of-state tuition"
			},
			maritalStatus: {
				"active": false,
				"value": 0,
				"0": "No",
				"1": "Yes"
			},
			children: {
				"active": false,
				"value": 0,
				"0": "No",
				"1": "Yes"
			},
			numberInFamily: {
				"active": false,
				"value": 0,
				"1": "One",
				"2": "Two",
				"3": "Three",
				"4": "Four",
				"5": "Five",
				"6": "Six or more"
			},
			numberInCollege: {
				"active": false,
				"value": 0,
				"textValue": ""
			},
			householdIncome: {
				"active": false,
				"value": 0,
				"0": "Less than $30, 000",
				"1": "Between $30, 000 - $39, 999",
				"2": "Between $40, 000 - $49, 999",
				"3": "Between $50, 000 - $59, 999",
				"4": "Between $60, 000 - $69, 999",
				"5": "Between $70, 000 - $79, 999",
				"6": "Between $80, 000 - $89, 999",
				"7": "Between $90, 000 - $99, 999",
				"8": "Above $99, 999"
			}
		};

	// Everything from this point on should be grabbed from the post meta
	// Lookup tables
	var efcDependent = [];
	var efcIndWithoutDep = [];
	var efcIndWithDep = [];

	var POATotal = [ "28206", "41288" ]; // Total. Why? This can just be calculated.
	var POATRF = [ "12428", "25510" ]; // Price of admission - tuition and fees [ "in-state", "out-of-state" ]
	var POARB = [ "11276", "11276" ]; // Price of admission - room and board [ "in-state", "out-of-state" ]
	var POABS = [ "960", "960" ]; // Price of admission - books and supplies [ "in-state", "out-of-state" ]
	var POAO = [ "3542", "3542" ]; // Price of admission - other [ "in-state", "out-of-state" ]

	// Total grant aid - [ "in-state", "out-of-state" ]
	var TGA0 = [ "17968", "9485" ];
	var TGA11000 = [ "17084", "6930" ];
	var TGA10012500 = [ "15148", "6180" ];
	var TGA25015000 = [ "13420", "3530" ];
	var TGA50017500 = [ "6500", "3250" ];
	var TGA750110000 = [ "4000", "5250" ];
	var TGA1000112500 = [ "1600", "9000" ];
	var TGA1250115000 = [ "2000", "9500" ];
	var TGA1500120000 = [ "2000", "9000" ];
	var TGA2000130000 = [ "2000", "10000" ];
	var TGA3000140000 = [ "1500", "9500" ];
	var TGA40000 = [ "2000", "9000" ];
	var TGANFAFSA = [ "2000", "8500" ]; // Not planning to apply for financial aid - [ "in-state", "out-of-state" ]

	// Step id definition:
	// 0 Introduction
	// 1 Age, Living Status, Residency Status
	// 2 Marital Status, Number of Children
	// 3 Number in Family, Number in College, Household Income
	// 4 Summary page
	// 5 Estimate
	function goNext() {

		// Variable to leverage for limiting the "Number of Family" options
		var limitFamilyOptions = false;

		// Step 0
		if ( step === "0" ) {
			goTo( "1" );
			return;
		}

		// Step 1
		if ( step === "1" ) {

			// Get the input values for this step
			var aid = getValue( "financial-aid" ),
				age = getValue( "age" ),
				resident = getValue( "residency" );

			// Validation - confirm that each field has been completed
			if ( aid === "" || age === "" || resident === "" ) {
				alert( "Please answer all questions before proceeding." );
				return;
			}

			// Validation - confirm that the Age value is a number
			if ( !$.isNumeric( age ) ) {
				alert( "Please enter integers only." );
				return;
			}

			// Save the input values into the dictionary
			dict.aid.active = true;
			dict.aid.value = aid;

			dict.age.active = true;
			dict.age.value = age;

			dict.residency.active = true;
			dict.residency.value = resident;

			// Rules
			if ( dict.aid.value === "1" ) {

				// If the user is not applying for financial aid, jump straight to the summary
				generateSummary();

				goTo( "4" );
			} else {

				// Hide/show marital status depending on age
				if ( dict.age.value > 23 ) {
					$( "#marital-status" ).hide();
				} else {
					$( "#marital-status" ).show();
				}

				goTo( "2" );
			}

			return;
		}

		// Step 2
		if ( step === "2" ) {

			// Get the input values for this step
			var married = getValue( "maritalstatus" ),
				children = getValue( "numberofchildren" ),

				// Cache selectors for use in the rules
				$dependent = $( ".dependent" ),
				$independent = $( ".independent" ),
				$children = $( ".children" ),
				$noChildren = $( ".no-children" );

			// Validate
			if ( ( dict.age.value < 24 && married === "" ) || children === "" ) {
				alert( "Please answer all questions before proceeding." );
				return;
			}

			// Save the input values into the dictionary
			if ( dict.age.value < 24 ) {
				dict.maritalStatus.active = true;
				dict.maritalStatus.value = married;
			} else {
				dict.maritalStatus.active = false;
			}

			dict.children.active = true;
			dict.children.value = children;

			// Rules
			if ( dict.age.value > 23 || dict.children.value === "1" || dict.maritalStatus.value === "1" ) {

				dependent = false;

				$dependent.hide();
				$independent.show();

				if ( dict.children.value === "1" ) {
					$children.show();
					$noChildren.hide();

					// All "Number in Family" options in the next step should be shown
					limitFamilyOptions = false;
				} else {
					$children.hide();
					$noChildren.show();

					// Only two "Number in Family" options in the next step should be shown
					limitFamilyOptions = true;
				}
			} else {
				dependent = true;

				$dependent.show();
				$independent.hide();
			}

			// Reset the "Number in Family" value if it is greater than two and the options will be limited to two.
			if ( limitFamilyOptions === true ) {
				if ( getValue( "numinfamily" ) > 2 ) {
					reset( "numinfamily" );
				}
			}

			// Reset the "Number in College" value if different options are going to be displayed.
			var inCollege = getValue( "numincollege" ).split( "|" );
			if ( inCollege[ 0 ] === "Two or more" || inCollege[ 0 ] === "Two" ) {
				reset( "numincollege" );
			}

			goTo( "3" );

			return;
		}

		// Step 3
		if ( step === "3" ) {

			// Get the input values for this step
			var family = getValue( "numinfamily" ),
				college = getValue( "numincollege" ).split( "|" ),
				collegeText = college[ 0 ],
				collegeValue = college[ 1 ],
				income = getValue( "householdincome" );

			// Validate
			if ( family === "" || collegeText === "" || income === "" ) {
				alert( "Please answer all questions before proceeding." );
				return;
			}

			if ( collegeValue > family ) {
				alert( "The Number in College must be less than the specified Number in Family." );
				return;
			}

			// Save entered values into dictionary
			dict.numberInFamily.active = true;
			dict.numberInFamily.value = family;

			dict.numberInCollege.active = true;
			dict.numberInCollege.value = collegeValue;
			dict.numberInCollege.textValue = collegeText;

			dict.householdIncome.active = true;
			dict.householdIncome.value = income;

			generateSummary();

			goTo( "4" );

			return;
		}

		// Summary review
		if ( step === "4" ) {
			generateReport();

			goTo( "5" );
		}
	}

	function goPrevious() {
		if ( step !== "5" && step !== "4" ) {
			goTo( "" + ( step - 1 ) );
		} else if ( step === "4" ) {
			goTo( "2" );
		} else {
			goTo( "3" );
		}
	}

	function goTo( stepid ) {
		if ( typeof stepid !== "undefined" ) {
			$( ".npc-step" ).hide();
			$( "#npc-step-" + stepid ).show();

			step = stepid;
		}
	}

	function generateReport() {
		var efc = 0,
			lookupColumn = "-1",
			setga = $( "#s_etga" ),
			senp = $( "#s_enp" ),
			x = 0,
			y = 0;

		if ( dict.aid.value === 0 ) {
			efc = getEFC();
		}

		lookupColumn = dict.residency.value;

		if ( lookupColumn === "-1" ) {
			return;
		}

		x = POATotal[ lookupColumn ];

		$( "#s_etpoa" ).html( formatCurrency( x ) );
		$( "#s_etf" ).html( formatCurrency( POATRF[ lookupColumn ] ) );
		$( "#s_erb" ).html( formatCurrency( POARB[ lookupColumn ] ) );
		$( "#s_ebs" ).html( formatCurrency( POABS[ lookupColumn ] ) );
		$( "#s_eo" ).html( formatCurrency( POAO[ lookupColumn ] ) );

		if ( setga ) {
			if ( dict.aid.value === 1 ) {
				y = TGANFAFSA[ lookupColumn ]; // NON-FAFSA
			} else if ( efc === 0 ) {
				y = TGA0[ lookupColumn ];
			} else if ( efc >= 1 && efc <= 1000 ) {
				y = TGA11000[ lookupColumn ];
			} else if ( efc * 1001 >= 1 && efc <= 2500 ) {
				y = TGA10012500[ lookupColumn ];
			} else if ( efc * 2501 >= 1 && efc <= 5000 ) {
				y = TGA25015000[ lookupColumn ];
			} else if ( efc >= 5001 && efc <= 7500 ) {
				y = TGA50017500[ lookupColumn ];
			} else if ( efc >= 7501 && efc <= 10000 ) {
				y = TGA750110000[ lookupColumn ];
			} else if ( efc >= 10001 && efc <= 12500 ) {
				y = TGA1000112500[ lookupColumn ];
			} else if ( efc >= 12501 && efc <= 15000 ) {
				y = TGA1250115000[ lookupColumn ];
			} else if ( efc >= 15001 && efc <= 20000 ) {
				y = TGA1500120000[ lookupColumn ];
			} else if ( efc >= 20001 && efc <= 30000 ) {
				y = TGA2000130000[ lookupColumn ];
			} else if ( efc >= 30001 && efc <= 40000 ) {
				y = TGA3000140000[ lookupColumn ];
			} else if ( efc >= 40001 ) {
				y = TGA40000[ lookupColumn ];
			}
			setga.innerHTML = formatCurrency( y );
		}
		if ( senp ) {
			var z = x - y * 1;
			senp.innerHTML = formatCurrency( z );
		}
	}

	function getEFC() {
		var efc = 0;
		if ( dependent === true ) {
			var arrayLength = efcDependent.length;
			for ( var i = 0; i < arrayLength; i++ ) {
				if ( efcDependent[ i ].numberInCollege === dict.numberInCollege.value && efcDependent[ i ].numberInFamily === dict.numberInFamily.value ) {
					efc = efcDependent[ i ].incomeRanges[ dict.householdIncome.value ];
					break;
				}
			}

		} else {
			if ( dict.children.value === 0 ) {

				// Without children
				var arrayLength = efcIndWithoutDep.length;
				for ( var i = 0; i < arrayLength; i++ ) {
					if ( efcIndWithoutDep[ i ].numberInCollege === dict.numberInCollege.value && efcIndWithoutDep[ i ].numberInFamily === dict.numberInFamily.value ) {
						efc = efcIndWithoutDep[ i ].incomeRanges[ dict.householdIncome.value ];
						break;
					}
				}
			} else {

				// With children
				var arrayLength = efcIndWithDep.length;
				for ( var i = 0; i < arrayLength; i++ ) {
					if ( efcIndWithDep[ i ].numberInCollege === dict.numberInCollege.value && efcIndWithDep[ i ].numberInFamily === dict.numberInFamily.value ) {
						efc = efcIndWithDep[ i ].incomeRanges[ dict.householdIncome.value ];
						break;
					}
				}
			}
		}
		return efc;
	}

	// Generate a summary table with the user's input.
	function generateSummary() {
		var html = "<table class='npc-table summary'>";

		// Step 1
		if ( dict.aid.active ) {
			html += "<tr><td>Financial Aid</td><td>" + dict.aid[ dict.aid.value ] + "</td></tr>";
		}

		if ( dict.age.active ) {
			html += "<tr><td>Age</td><td>" + dict.age.value + "</td></tr>";
		}

		if ( dict.residency.active ) {
			html += "<tr><td>Residency</td><td>" + dict.residency[ dict.residency.value ] + "</td></tr>";
		}

		// Step 2
		if ( dict.maritalStatus.active ) {
			html += "<tr><td>Marital Status</td><td>" + dict.maritalStatus[ dict.maritalStatus.value ] + "</td></tr>";
		}

		if ( dict.children.active ) {
			html += "<tr><td>Children</td><td>" + dict.children[ dict.children.value ] + "</td></tr>";
		}

		// Step 3
		if ( dict.numberInFamily.active ) {
			html += "<tr><td>Number in Family</td><td>" + dict.numberInFamily[ dict.numberInFamily.value ] + "</td></tr>";
		}

		if ( dict.numberInCollege.active ) {
			html += "<tr><td>Number in College</td><td>" + dict.numberInCollege[ dict.numberInCollege.value ] + "</td></tr>";
		}

		if ( dict.householdIncome.active ) {
			html += "<tr><td>Household Income</td><td>" + dict.householdIncome[ dict.householdIncome.value ] + "</td></tr>";
		}

		html += "</table>";

		$( "#dv_summary" ).html( html );
	}

	// Set variable values back to default.
	function clearVars() {
		dependent = "";
		step = "0";

		$.each( dict, function( key, value ) {
			dict[ key ].active = false;
			dict[ key ].value = 0;

			if ( dict[ key ].textValue ) {
				dict[ key ].textValue = "";
			}
		} );
	}

	// Reset input.
	function reset( name ) {
		var $input = "input[name=" + name + "]";

		if ( "radio" === $( $input ).attr( "type" ) ) {
			$( $input + ":checked" ).removeAttr( "checked" );
		}

		if ( "text" === $( $input ).attr( "type" ) ) {
			$( $input ).val( "" );
		}
	}

	// Get the value of the selected radio button.
	function getValue( name ) {
		var $input = "input[name=" + name + "]",
			value = "";

		if ( "radio" === $( $input ).attr( "type" ) && $( $input ).is( ":checked" ) ) {
			value = $( $input + ":checked" ).val();
		}

		if ( "text" === $( $input ).attr( "type" ) ) {
			value = $( $input ).val();
		}

		return value;
	}

	// Format the number to a monetary value.
	function formatCurrency( num ) {
		num = num.toString().replace( /\$|\, /g, "" );

		if ( isNaN( num ) ) {
			num = "0";
		}

		num = Math.floor( ( num * 100 + 0.50000000001 ) / 100 ).toString();

		for ( var i = 0; i < Math.floor( ( num.length - ( 1 + i ) ) / 3 ); i++ ) {
			num = num.substring( 0, num.length - ( 4 * i + 3 ) ) + "," +
			num.substring( num.length - ( 4 * i + 3 ) );
		}

		return ( "$" + num );
	}

	// Handle form button clicks.
	$( document ).ready( function() {

		$( ".figures-from" ).html( annualData.figuresFrom );

		$( ".npc-button" ).click( function( e ) {
			e.preventDefault();

			if ( $( this ).hasClass( "next" ) ) {
				goNext();
			}

			if ( $( this ).hasClass( "previous" ) ) {
				if ( dict.aid.value === "1" ) {
					goTo( "1" );
				} else {
					goPrevious();
				}
			}

			if ( $( this ).hasClass( "modify" ) ) {
				clearVars();
				goTo( "1" );
			}

			if ( $( this ).hasClass( "start-over" ) ) {
				$( ".npc input" ).each( function() {
					reset( $( this ).attr( "name" ) );
				} );

				clearVars();
				goTo( "0" );
			}
		} );
	} );
}( jQuery, annualData ) );
