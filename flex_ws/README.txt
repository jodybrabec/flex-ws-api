

Test classes using a_test.php



============================== GROUPS and Number Ranges ===================================

* Permit Number Ranges (PNR)
	Permit Number Range Management - example for 12CHY prefix (which is range 12CHY4005 - 12CHY6000):
		Series Type			Inventoried
		Series Prefix		12CHY
		Series Start		4005
		Series End			6000		- editable
		Series Char Width	9			- length(Series Prefix) + length(Series Start or End)
		Description						- Cherry Avenue Garage

* Permission Control Groups (PCG)
	Example for Control Groups 12 Cherry Ave Garage Permit, for Number Range 12CHY4005 - 12CHY6000 (which is 12CHY):
		12 Cherry Ave Garage Permit		Cherry Avenue Garage Subclassifications(20)	Facilities(3)	Fees(1)	Times(1)
		12 Cherry Avenue Garage Carpool	Cherry Avenue Garage Subclassifications(19)	Facilities(1)	Fees(1)	Times(1)
	PCG divides (classifies) PNR, so that you can sell permits.
	Sales Restricted to Waiting List checked - means SHOULD BE linked to a waitlist (WTL).  PEC_FAC.....
	Valid Facilities for Number Range 12CHY4005 - 12CHY6000; Facilities 12 Cherry Ave Garage Permit - PEC_FAC_ELIGIBILITY will have one record for each eligible facility.
	Fee for 12CHY4005 - 12CHY6000 Cherry Avenue Garage: Fixed Fee/Return Schedule Parameters

* Waitlists (WTL)
	Waiting List Management - example of Cherry Avenue Garage WTL:
		Target: Control Group:														- 12 Cherry Ave Garage Permit.
		Multiple Choice Waiting List?												- Yes.
		Maximum Number of Active Customer Requests (0 for no limit):	- 0


* Facilities (FAC)

* Permission Inventory PNR
