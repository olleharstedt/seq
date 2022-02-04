Test of command object queue as a way to lift out side-effects, making functions more testable that way.

## Survey activator

* Flush cache
* Fire event "beforeSurveyActivate"
* Calculate field map
* Create response database table
* Create survey folder
* Set survey active = 'Y'
* Fire event "afterSurveyActivate"

## Notes

* The outmost shell is at script or app shutdown - run queue at that point?
* Sacrificing performance for testability
* How is composability affected?
