<?php
/**
 * Deploy environment identifiers. The active one is exposed via the ENVIRONMENT
 * constant (set in config); code compares against these, e.g.
 * `ENVIRONMENT === Environment::PRODUCTION` gates prod-only side effects.
 */
class Environment {
    const PRODUCTION = 0;
    const DEVELOPMENT = 1;
}