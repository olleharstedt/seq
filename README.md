Test of command object queue as a way to lift out side-effects, making functions more testable that way.

Compare: Functional core, imperative shell

Purpose: Extend the functional core even when side-effects are entangled with business logic

## que

* Since not all side-effects have to happen at once, you can install a queue that runs at app shutdown
* Usage: `que(fn () => ...)`
* Whatever is in the queue is executed, in order of definition, at app shutdown

## app

* `app` is used when you need the result of a side-effect immediately
* During mocking, you give `app` a configuration object to tell it what to return

## seq

* When a side-effect depends on the result of a previous side-effect, e.g. create file if it doesn't already exist, you can sequence closures together

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
