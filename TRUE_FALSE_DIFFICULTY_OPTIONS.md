# True/False Game Difficulty Options - Decision Document

## Current Situation

We have a **True/False game** for English language learners where:
- Students hear/read statements and decide if they're true or false
- Questions are generated using AI (OpenAI) with prompts that mention "CEFR A1 level"
- Currently, "CEFR A1 level" is only used as a prompt instruction for AI generation - it's not enforced, validated, or displayed to students
- The game has no difficulty settings or filtering options

## Goal

We want to give **admins more control to make the True/False game harder** for students. We need to decide on the best approach for implementing difficulty levels.

## Current System Context

### Grammar Sets (Already Exist)
- **GrammarSet** model: Contains a collection of grammar concepts (e.g., "Grade 7 Grammar Set")
- **GrammarConcept** model: Individual grammar topics (e.g., "Modals and Semi-modals - can", "Present Simple", "Past Perfect")
- Grammar sets are **already used** in our "Clause Exercise" feature (fill-in-the-blank exercises)
- Grammar concepts have structured data: `grammar_topic`, `grammar_sub_topic`, `section`
- Grammar sets can be associated with lessons

### True/False Questions (Current State)
- Questions have: `statement`, `is_true`, `explanation`, `category`, `audio_path`
- Questions belong to a `lesson_id`
- No current relationship to grammar sets or difficulty levels
- AI generation uses CEFR A1 prompts but doesn't enforce it

## Two Options for Implementing Difficulty

---

## Option 1: Use Grammar Sets (Curriculum-Aligned)

### Approach
- Link True/False questions to **Grammar Sets** (similar to how Clause Exercises work)
- When creating/generating questions, admins select a Grammar Set
- Questions test understanding of specific grammar concepts from that set
- Difficulty is determined by which grammar concepts are selected (basic vs advanced)

### Implementation
1. Add `grammar_set_id` field to `true_false_questions` table
2. Update create/edit forms to allow selecting a Grammar Set
3. Update AI generation prompts to focus on grammar concepts from selected set
4. Add filtering in play view to show questions by grammar set
5. Optionally categorize concepts by difficulty (basic/intermediate/advanced)

### Pros
✅ **Curriculum-aligned**: Matches existing lesson structure and grammar teaching  
✅ **Reuses existing data**: Grammar sets already defined and organized  
✅ **Consistent**: Same system used in Clause Exercises  
✅ **Specific**: Tests actual grammar concepts being taught  
✅ **Flexible**: Can filter by specific grammar sets or concepts  
✅ **Pedagogically sound**: Questions align with what students are learning  

### Cons
❌ **Requires grammar sets**: Only works if grammar sets are well-defined  
❌ **Less flexible**: Difficulty tied to specific grammar concepts  
❌ **More complex**: Need to map concepts to difficulty levels  

---

## Option 2: Use CEFR Levels (Standardized)

### Approach
- Add a `cefr_level` field to True/False questions (A1, A2, B1, B2, etc.)
- When generating questions, admins select desired CEFR level
- AI prompts explicitly enforce the selected CEFR level
- Questions are filtered/grouped by CEFR level when playing

### Implementation
1. Add `cefr_level` enum field to `true_false_questions` table (A1, A2, B1, B2, C1, C2)
2. Update create/edit forms with CEFR level dropdown
3. Update AI generation prompts to strictly enforce selected CEFR level
4. Add filtering in play view to show questions by CEFR level
5. Optionally validate that generated questions match the CEFR level

### Pros
✅ **Standardized**: CEFR is internationally recognized language proficiency framework  
✅ **Clear difficulty progression**: A1 (beginner) → A2 → B1 → B2 → C1 → C2 (advanced)  
✅ **Simple**: Easy to understand and implement  
✅ **Flexible**: Can mix questions from different CEFR levels  
✅ **Validated framework**: CEFR has clear criteria for each level  

### Cons
❌ **Abstract**: Doesn't align with specific curriculum/grammar concepts being taught  
❌ **Not curriculum-aligned**: May not match what students are actually learning  
❌ **Requires validation**: Need to ensure AI actually generates appropriate level  
❌ **Less specific**: Doesn't target specific grammar concepts  

---

## Comparison Summary

| Aspect | Grammar Sets | CEFR Levels |
|--------|-------------|-------------|
| **Alignment with curriculum** | ✅ High | ❌ Low |
| **Reuses existing data** | ✅ Yes | ❌ No |
| **Difficulty clarity** | ⚠️ Medium (needs mapping) | ✅ High (standardized) |
| **Implementation complexity** | ⚠️ Medium | ✅ Low |
| **Pedagogical value** | ✅ High (tests specific concepts) | ⚠️ Medium (general proficiency) |
| **Flexibility** | ✅ High (can filter by concept) | ✅ High (can filter by level) |

## Questions to Consider

1. **Do you want difficulty to align with your specific curriculum/grammar concepts?**
   - If yes → Grammar Sets
   - If no → CEFR Levels

2. **Do you already have well-defined grammar sets with concepts organized by difficulty?**
   - If yes → Grammar Sets makes sense
   - If no → CEFR Levels might be easier

3. **Do you want students to practice specific grammar concepts they're learning?**
   - If yes → Grammar Sets
   - If no → CEFR Levels

4. **Do you want a standardized, internationally recognized difficulty system?**
   - If yes → CEFR Levels
   - If no → Grammar Sets

## Recommendation Needed

Which approach would be better for:
- **Admin control** over difficulty
- **Student learning** outcomes
- **System consistency** with existing features
- **Long-term maintainability**

---

## Technical Context

- **Framework**: Laravel (PHP)
- **Database**: MySQL
- **AI**: OpenAI API (GPT-4o-mini / GPT-4o)
- **Existing Models**: `GrammarSet`, `GrammarConcept`, `TrueFalseQuestion`, `Lesson`
- **Related Feature**: Clause Exercises already use Grammar Sets successfully
