<?php

namespace Discord;

/**
 * Discord interaction response flag constants (e.g. marking a response ephemeral).
 */
abstract class InteractionResponseFlags {
    const EPHEMERAL = 1 << 6;
}