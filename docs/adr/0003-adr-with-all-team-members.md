# 3. Invite all team members for architecture decisions {#adr_0003}

Date: 2018-07-04

## Status

accepted

## Context

We use architectural decision records (ADR) on this project.

The project's code base is owned by the team, the team organizes itself (see Journey model) into smaller, short lived "journey" sub-units to - amongst others - increase focus. Architecture decisions affect the project for a long time, and will likely soon be faced by developers that were not part of the journey at the time. Consequently their feedback about the architectural decision is inevitable. Additionally, given the intentionally small size of an individual journey's group, the amount of opinions concerning any given ADR could be as small as one or two, should the ADR be voted upon by members of the same journey exclusively. To avoid a flood of ADRs trying to unwrite each other and to increase the standing of ADRs in general and the quality of the individual ADR they should be vetted (RFC) by the entire team.

To avoid long-running feedback loops that block the individual journey team from fulfilling their commitments RFCs should be time-limited.

## Decision

We put proposed Architecture Decision Records up for feedback by the entire team. ADRs will be proposed as dedicated changes and iterated upon through the tools provided by our code review system. ADR RFCs will have a deadline; it should be no sooner than one working day after initial request.

## Consequences

All team members have the possibility to participate in shaping the architecture decision made on the products the team is concerned with. Given the increased group size and the possible unfamiliarity of some team member with the specialized team's subject matter likely will cause additional work in clarifying but at the same time add more brains to find the best possible solution, facilitate knowledge transfer, and ensure that ADRs are written in a way that is understandable to a broad audience.
