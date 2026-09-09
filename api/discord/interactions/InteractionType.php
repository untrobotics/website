<?php

namespace Discord;

/**
 * Discord interaction type constants (the "type" field of an interaction payload).
 */
abstract class InteractionType {
    const PING = 1;
    const APPLICATION_COMMAND = 2;
    const MESSAGE_COMPONENT = 3;
}