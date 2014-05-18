package com.fathzer.soft.javaluator.junit;

import static org.junit.Assert.*;

import java.util.Arrays;
import java.util.HashMap;
import java.util.Iterator;
import java.util.Map;

import org.junit.Test;

import com.fathzer.soft.javaluator.AbstractEvaluator;
import com.fathzer.soft.javaluator.Operator;
import com.fathzer.soft.javaluator.Parameters;

public class TextOperatorsTest {

	@Test
	public void test() {
		Map<String,String> variableToValue = new HashMap<String, String>();
		variableToValue.put("type", "PORT");
		MyEvaluator evaluator = new MyEvaluator();
		assertTrue(evaluator.evaluate("type=PORT", variableToValue));
		assertFalse(evaluator.evaluate("type=NORTH", variableToValue));
		assertTrue(evaluator.evaluate("type=PORT AND true", variableToValue));
	}

	private static class MyEvaluator extends AbstractEvaluator<Boolean> {
		/** The negate unary operator. */
		public final static Operator NEGATE = new Operator("NOT", 1, Operator.Associativity.RIGHT, 3);
		/** The logical AND operator. */
		private static final Operator AND = new Operator("AND", 2, Operator.Associativity.LEFT, 2);
		/** The logical OR operator. */
		public final static Operator OR = new Operator("OR", 2, Operator.Associativity.LEFT, 1);
		
		private static final Parameters PARAMETERS;

		static {
			// Create the evaluator's parameters
			PARAMETERS = new Parameters();
			// Add the supported operators
			PARAMETERS.add(AND);
			PARAMETERS.add(OR);
			PARAMETERS.add(NEGATE);
		}

		protected MyEvaluator() {
			super(PARAMETERS);
		}

		@Override
		protected Boolean toValue(String literal, Object evaluationContext) {
			int index = literal.indexOf('=');
			if (index>=0) {
				String variable = literal.substring(0, index);
				String value = literal.substring(index+1);
				System.out.println ("variable test detected is "+variable+" equals to "+value);
				return value.equals(((Map<String, String>)evaluationContext).get(variable));
			} else {
				System.out.println ("constant detected "+literal);
				return Boolean.valueOf(literal);
			}
		}

		@Override
		protected Boolean evaluate(Operator operator,
				Iterator<Boolean> operands, Object evaluationContext) {
			if (operator == NEGATE) {
				return !operands.next();
			} else if (operator == OR) {
				Boolean o1 = operands.next();
				Boolean o2 = operands.next();
				return o1 || o2;
			} else if (operator == AND) {
				Boolean o1 = operands.next();
				Boolean o2 = operands.next();
				return o1 && o2;
			} else {
				return super.evaluate(operator, operands, evaluationContext);
			}
		}

		@Override
		protected Iterator<String> tokenize(String expression) {
			return Arrays.asList(expression.split("\\s")).iterator();
		}
	}
}
